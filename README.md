# Monorail
Monorail is a tiny closure queuing system for when you want to do one thing really fast.

## Install
```bash
composer require mmeyer2k/monorail
```

## Basic Usage and Features
```php
(new \mmeyer2k\Monorail\Task)
    ->push(function () {
        echo "hello world";
    });
```

### Priorities
Monorail supports numeric priority levels from 1 to 5 with 3 being default and 1 being highest priority.
```php
(new \mmeyer2k\Monorail\Task)
    ->priority(1)
    ->push(function () {
        // do something really important
    });
```

### Tubes

### Delays

### Re-queues

## Supervisor

