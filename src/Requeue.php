<?php

namespace mmeyer2k\Monorail;

class Requeue
{
    protected $tube = 'default';
    protected $delay = 0;
    protected $priority = 3;
    protected $tries = 3;

    /**
     * @param string $tube
     * @return Task
     */
    public function tube(string $tube): self
    {
        $this->tube = $tube;

        return $this;
    }

    public function tries(int $tries = 3): self
    {
        if ($tries < 1) {
            throw new \InvalidArgumentException("Tries value must be greater than 0");
        }

        $this->tries = $tries;

        return $this;
    }

    /**
     * @param int $priority
     * @return Task
     * @throws \InvalidArgumentException
     */
    public function priority(int $priority = 3): self
    {
        if ($priority > 5 || $priority < 1) {
            throw new \InvalidArgumentException("Priority values can only be 1 - 5");
        }

        $this->priority = $priority;

        return $this;
    }

    /**
     * @param int $delay
     * @return Task
     */
    public function delay(int $delay = 0): self
    {
        $this->delay = $delay;

        return $this;
    }
}