<?php

declare(strict_types=1);

namespace Manychois\Cici\Utilities;

/**
 * Provides utility methods for JSON operations.
 */
final class Json
{
    /**
     * Initializes a new instance of the JsonUtility class.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
        // no-op
    }

    /**
     * Encodes a value to a JSON string.
     *
     * @param mixed $value The value to encode.
     *
     * @return string The JSON string.
     */
    public static function encode(mixed $value): string
    {
        return \json_encode($value, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
    }
}
