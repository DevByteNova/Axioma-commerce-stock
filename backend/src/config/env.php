<?php

function axioma_env(string $key, string $default = ''): string
{
    static $values;

    if ($values === null) {
        $values = [];
        $envFile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.env';

        if (is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                $values[$name] = trim($value, "\\\"'");
            }
        }
    }

    return $values[$key] ?? $default;
}
