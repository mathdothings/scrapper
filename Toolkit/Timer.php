<?php

namespace App\Toolkit;

/**
 * Precision timing utility for performance measurement.
 * 
 * Provides methods to track elapsed time between start and end points,
 * with microsecond precision using PHP's microtime().
 * 
 * @package App\Toolkit
 */
class Timer
{
    /** 
     * @var float UNIX timestamp with microseconds when timer started 
     */
    private float $start;

    /** 
     * @var float|null UNIX timestamp with microseconds when timer ended 
     */
    private float $end;

    /**
     * Starts the timer.
     * 
     * @return void
     * 
     * @example
     * $timer = new Timer();
     * $timer->start();
     */
    public function start(): void
    {
        $this->start = microtime(true);
    }

    /**
     * Stops the timer.
     * 
     * @return void
     * 
     * @example
     * $timer->end();
     * echo $timer->elapsed(); // Get duration
     */
    public function end(): void
    {
        $this->end = microtime(true);
    }

    /**
     * Calculates elapsed time in seconds.
     * 
     * If end() wasn't called, returns time since start().
     * 
     * @return float Duration in seconds (with microsecond precision)
     * 
     * @throws \RuntimeException If timer wasn't started
     */
    public function elapsed(): float
    {
        if (!isset($this->start)) {
            throw new \RuntimeException('Timer must be started before calculating elapsed time');
        }

        return $this->end ?? microtime(true) - $this->start;
    }

    /**
     * Restarts the timer and returns elapsed time.
     * 
     * @return float Elapsed time between start() and restart() in seconds
     * 
     * @example
     * $timer->start();
     * // ... code ...
     * $elapsed = $timer->elapsed(); // Returns time and resets
     */
    public function restart(): float
    {
        $elapsed = $this->elapsed();
        $this->start();
        return $elapsed;
    }
}
