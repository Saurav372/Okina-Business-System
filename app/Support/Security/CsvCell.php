<?php

namespace App\Support\Security;

final class CsvCell
{
    public static function literal(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^(?:[\x00-\x20]*[=+\-@]|[\t\r\n])/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
