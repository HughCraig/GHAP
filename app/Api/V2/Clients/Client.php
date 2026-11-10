<?php

namespace TLCMap\Api\V2\Clients;

/**
 * A registered v2 API client: its name (the throttle key and the name logged) and its limit.
 * The client's key and its hash are deliberately not held here.
 */
final class Client
{
    /** @var string */
    private $name;

    /** @var int */
    private $maxAttempts;

    /** @var int */
    private $decayMinutes;

    public function __construct(string $name, int $maxAttempts, int $decayMinutes)
    {
        $this->name = $name;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function decayMinutes(): int
    {
        return $this->decayMinutes;
    }
}
