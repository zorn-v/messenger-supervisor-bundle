<?php

namespace ZornV\Symfony\MessengerSupervisorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class MessengerSupervisorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
/* validation
        $transports = array_keys($config['transports']);
        $buses = array_keys($config['buses']);
        foreach ($config['supervisor'] as $name => $consumer) {
            foreach ($consumer['receivers'] as $receiver) {
                if (!\in_array($receiver, $transports)) {
                    throw new InvalidConfigurationException(sprintf('Invalid receiver "%s" in "%s" messenger consumer. Available transports are "%s"', $receiver, $name, implode('", "', $transports)));
                }
            }
            if (isset($consumer['bus']) && !\in_array($consumer['bus'], $buses)) {
                throw new InvalidConfigurationException(sprintf('Invalid bus "%s" in "%s" messenger consumer. Available buses are "%s"', $consumer['bus'], $name, implode('", "', $buses)));
            }
        }
        $container->setParameter('messenger.supervisor', $config['supervisor']);
*/
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition('console.command.messenger_supervisor');
        $definition->replaceArgument(1, $config);
    }
}
