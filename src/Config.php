<?php

namespace App;

class Config {
    private static array $config = [];

    public static function load(string $path): void {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            self::$config[trim($name)] = trim($value);
        }

        // Also load system environment variables if they exist (for Docker)
        foreach (getenv() as $key => $value) {
            if (!isset(self::$config[$key])) {
                self::$config[$key] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed {
        return self::$config[$key] ?? $default;
    }
}
