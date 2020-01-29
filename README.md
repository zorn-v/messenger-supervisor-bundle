# Symfony messenger supervisor bundle

Inspired by laravel horizon, supervisor for `messenger:consume` commands.

Runs `messenger:consume` commands with parameters from config and watch that commands is running and start they if needed.


## Installation

`composer require zorn-v/messenger-supervisor-bundle`


## Usage

`./bin/console messenger:supervisor`

#### Config

In config all parameters have same names as `messenger:consume` parameters.
You can check by `./bin/console messenger:consume --help`

Also check symfony documentation about best practice https://symfony.com/doc/current/messenger.html#deploying-to-production

```yaml
# config/packages/messenger_supervisor.yaml
messenger_supervisor:
    queue-1: ~
    queue-2:
        receivers: [in_memory]
        memory-limit: 128M
        time-limit: 3600
        limit: 1000
        sleep: 1
        bus: mybus
    queue-3:
        limit: 100
```

## Deploy

You also need to set up `messenger:supervisor` in system supervisor for autostart this command and manage it via command like `service messenger-supervisor restart`

#### systemd

``systemd`` is standard init system on most linux distros.
Create unit in ``/etc/systemd/system`` dir:

```ini
#/etc/systemd/system/messenger-supervisor.service

[Unit]
Description=Symfony messenger supervisor
After=network.target

[Service]
Type=exec
User=www-data
Restart=always
ExecStart=/path/to/your/app/bin/console messenger:supervisor

[Install]
WantedBy=multi-user.target
```

Change ``user`` to the Unix user on your server if needed.
Now tell systemd about new unit, enable it for run at system start and run it

```bash
$ sudo systemctl daemon-reload
$ sudo systemctl enable messenger-supervisor
$ sudo systemctl start messenger-supervisor
```