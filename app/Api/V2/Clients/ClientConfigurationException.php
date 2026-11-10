<?php

namespace TLCMap\Api\V2\Clients;

/**
 * The client configuration is malformed. The message names the offending entry by position (and
 * by name only when the name is valid), and never contains a key or a hash.
 */
class ClientConfigurationException extends \RuntimeException
{
}
