<?php

namespace mmeyer2k\Monorail;

class Requeue
{
    private $tube = 'default';
    private $delay = 0;
    private $priority = 5;
}