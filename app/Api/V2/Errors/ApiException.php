<?php

namespace TLCMap\Api\V2\Errors;

class ApiException extends \RuntimeException
{
    /** @var int */
    private $status;

    /** @var string */
    private $errorCode;

    /** @var array */
    private $details;

    /** @var array */
    private $headers;

    public function __construct(int $status, string $code, string $message, array $details = [], array $headers = [])
    {
        parent::__construct($message);
        $this->status = $status;
        $this->errorCode = $code;
        $this->details = $details;
        $this->headers = $headers;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function details(): array
    {
        return $this->details;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public static function invalidParameter(string $parameter, string $rule, string $accepts, string $message): self
    {
        return new self(400, 'INVALID_PARAMETER', $message, [
            'parameter' => $parameter,
            'rule' => $rule,
            'accepts' => $accepts,
        ]);
    }

    public static function invalidGeometry(string $parameter, string $reason): self
    {
        return new self(400, 'INVALID_GEOMETRY', "The geometry in '$parameter' is not valid: $reason", [
            'parameter' => $parameter,
            'reason' => $reason,
        ]);
    }

    public static function filterRejected(string $expression, int $position, string $expected): self
    {
        return new self(400, 'FILTER_REJECTED', "The extended_data filter was rejected at position $position; expected $expected.", [
            'expression' => $expression,
            'position' => $position,
            'expected' => $expected,
        ]);
    }

    public static function invalidCredential(): self
    {
        return new self(401, 'INVALID_CREDENTIAL', 'The credential in the Authorization header is not valid. Send no Authorization header to use the anonymous limit.', [], [
            'WWW-Authenticate' => 'Bearer error="invalid_token"',
        ]);
    }

    public static function layerPrivate(int $layerId): self
    {
        return new self(403, 'LAYER_PRIVATE', "Layer $layerId exists but is not public.", [
            'layer_id' => $layerId,
        ]);
    }

    public static function layerNotFound(int $layerId): self
    {
        return new self(404, 'LAYER_NOT_FOUND', "Layer $layerId does not exist.", [
            'layer_id' => $layerId,
        ]);
    }
}
