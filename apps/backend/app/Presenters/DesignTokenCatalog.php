<?php

namespace App\Presenters;

class DesignTokenCatalog
{
    /**
     * Cache container to store normalized configurations.
     *
     * @var array
     */
    private static array $cache = [];

    /**
     * Get a specific normalized section from design tokens config.
     *
     * @param string $name
     * @return array
     */
    public static function section(string $name): array
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        // Fetch using standard Laravel configuration subsystem
        $data = config("design-tokens.{$name}", []);

        // Fully normalize structures to avoid leaking associative index offsets in Blade loops
        self::$cache[$name] = collect($data)
            ->map(fn($item) => (array)$item)
            ->values()
            ->all();

        return self::$cache[$name];
    }

    /**
     * Helper wrappers for specific categories.
     */
    public static function colors(): array
    {
        return self::section('colors');
    }

    public static function typography(): array
    {
        return self::section('typography');
    }

    public static function spacing(): array
    {
        return self::section('spacing');
    }

    public static function elevation(): array
    {
        return self::section('elevation');
    }

    public static function motion(): array
    {
        return self::section('motion');
    }

    public static function semantic(): array
    {
        return self::section('semantic');
    }

    public static function charts(): array
    {
        return self::section('charts');
    }

    public static function guidelines(): array
    {
        $raw = config('design-tokens.guidelines', []);
        $index = $raw['index'] ?? [];

        $ordered = [];
        foreach ($index as $key => $label) {
            if (isset($raw[$key]) && is_array($raw[$key])) {
                $ordered[] = $raw[$key];
            }
        }

        return $ordered;
    }
}
