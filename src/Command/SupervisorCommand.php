<?php

namespace ZornV\Symfony\MessengerSupervisorBundle\Command;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @author zorn-v (https://github.com/zorn-v)
 */
class SupervisorCommand extends Command
{
    protected static $defaultName = 'messenger:supervisor';

    private $lockFactory;
    private $config;
    private $logger;

    public function __construct(LockFactory $lockFactory, array $config, LoggerInterface $logger = null)
    {
        $this->lockFactory = $lockFactory;
        $this->config = $config;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Run and watch messenger:consume commands with parameters from config')
            ->setDefinition([
                new InputOption('sleep', null, InputOption::VALUE_REQUIRED, 'Seconds to sleep after messenger:consume is started', 1),
            ])
            ->setHelp(<<<'EOF'
Config example:
<comment>
messenger_supervisor:
    queue-1:
        receivers:</> [in_memory, redis]
        <comment>sleep:</> 1
        <comment>limit:</> 1000
        <comment>time-limit:</> 3600
        <comment>memory-limit:</> 128M
        <comment>bus:</> mybus
    <comment>queue-2:</> ~
    <comment>queue-3:</> ~
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!\extension_loaded('pcntl')) {
            $io->error('pcntl extension must be installed and enabled to run this command');

            return 1;
        }

        if (empty($this->config)) {
            $io->warning('No consumers is defined in config. Exiting.');

            return 1;
        }

        $appHash = substr(sha1(__DIR__), 0, 8);
        $lockName = sprintf('messenger-supervisor-%s', $appHash);
        $lock = $this->lockFactory->createLock($lockName);
        if (!$lock->acquire()) {
            $io->error(sprintf('%s already running', $lockName));

            return 1;
        }

        $running = true;
        $consumers = [];
        $php = (new PhpExecutableFinder())->find();

        foreach ($this->config as $params) {
            $cmd = array_merge([$php, $_SERVER['PHP_SELF'], 'messenger:consume'], $params['receivers']);
            unset($params['receivers']);
            foreach ($params as $k => $v) {
                $cmd[] = sprintf('--%s=%s', $k, $v);
            }
            $consumers[] = new Process($cmd);
        }

        pcntl_signal(SIGTERM, function () use (&$running, $consumers) {
            $running = false;
            foreach ($consumers as $consumer) {
                $consumer->signal(SIGTERM);
            }
        });

        $sleep = $input->getOption('sleep');

        if (null !== $this->logger) {
            $this->logger->info('Messenger supervisor started');
        }

        while ($running) {
            foreach ($consumers as $consumer) {
                if (!$consumer->isRunning()) {
                    if (null !== $this->logger && $consumer->getExitCode()) {
                        $this->logger->error($consumer->getErrorOutput());
                        $this->logger->error(sprintf('Restaring messenger consumer: %s', $consumer->getCommandLine()));
                    }
                    $consumer->start();
                }
            }
            pcntl_signal_dispatch();
            usleep(100);
        }
        foreach ($consumers as $consumer) {
            $consumer->wait();
        }

        return 0;
    }
}
