# :monorail: Monorail
Monorail is a single-threaded, redis-backed queue processor for when you want to do one thing really fast.

## Install
```bash
composer require mmeyer2k/monorail
```

## Basic Usage and Features
```php
(new \mmeyer2k\Monorail\Queue)
    ->push(function () {
        echo "hello world";
    });
```

### Priorities
Monorail supports numeric priority levels from 1 to 5 with 3 being default and 1 being highest priority.
```php
(new \mmeyer2k\Monorail\Queue)
    ->priority(1)
    ->push(function () {
        // do something really important
    });
```

### Tubes
```php
(new \mmeyer2k\Monorail\Queue)
    ->tube('other tube')
    ->push(function () {
        echo "hello world";
    });
```

### Delays
```php
$seconds = 15;

(new \mmeyer2k\Monorail\Queue)
    ->delay($seconds)
    ->push(function () {
        echo "hello world";
    });
```

### Re-queues

## Supervisor
To make sure the queue worker is always running, supervisord is a great option.
```
[program:monorail-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/vendor/mmeyer2k/monorail/monorail work --tube=default
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/tmp/supervisor-monorail.log
```
