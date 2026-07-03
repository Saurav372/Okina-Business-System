<?php

namespace App\Support\Breadcrumbs;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class BreadcrumbBuilder
{
    private BreadcrumbDefinition $definition;

    public function __construct(BreadcrumbDefinition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Build a breadcrumb trail for a given route name and its parameters.
     *
     * @param string $routeName
     * @param array $parameters
     * @return BreadcrumbItem[]
     * @throws RuntimeException
     */
    public function build(string $routeName, array $parameters = []): array
    {
        $items = $this->definition->items();

        if (! isset($items[$routeName])) {
            return [];
        }

        $trail = [];
        $visited = [];
        $currentRoute = $routeName;

        // Traverse upwards to collect the hierarchy
        while ($currentRoute) {
            if (in_array($currentRoute, $visited, true)) {
                throw new RuntimeException("Circular breadcrumb dependency detected at route: {$currentRoute}");
            }

            $visited[] = $currentRoute;

            if (! isset($items[$currentRoute])) {
                break;
            }

            $def = $items[$currentRoute];
            
            // Unshift to place ancestors first
            array_unshift($trail, [
                'route' => $currentRoute,
                'def' => $def,
            ]);

            $currentRoute = $def['parent'] ?? null;
        }

        // Now map to BreadcrumbItem objects
        $resolvedTrail = [];
        $totalItems = count($trail);

        foreach ($trail as $index => $step) {
            $isLast = ($index === $totalItems - 1);
            
            $url = $isLast ? null : route($step['route'], $parameters);
            
            $resolvedLabel = $this->resolveLabel($step['def'], $parameters);

            $resolvedTrail[] = new BreadcrumbItem(
                label: $resolvedLabel,
                url: $url,
                active: $isLast
            );
        }

        return $resolvedTrail;
    }

    private function resolveLabel(array $def, array $parameters): string
    {
        $label = $def['label'];

        if (! Str::contains($label, '{') || ! Str::contains($label, '}')) {
            return $label;
        }

        // Example: {order.number}
        preg_match_all('/\{([^}]+)\}/', $label, $matches);

        foreach ($matches[1] as $placeholder) {
            $parts = explode('.', $placeholder, 2);
            $paramName = $parts[0];
            $property = $parts[1] ?? null;

            $model = $parameters[$paramName] ?? null;

            $resolvedValue = $this->resolveFallbackChain($model, $property, $def['fallback'] ?? null);

            $label = str_replace('{' . $placeholder . '}', $resolvedValue, $label);
        }

        return $label;
    }

    private function resolveFallbackChain(mixed $model, ?string $property, ?string $staticFallback): string
    {
        if (! $model) {
            return $staticFallback ?? 'Unknown';
        }

        if ($property && is_object($model) && isset($model->{$property})) {
            return (string) $model->{$property};
        }

        if (is_array($model) && $property && isset($model[$property])) {
            return (string) $model[$property];
        }

        if (is_object($model)) {
            if (method_exists($model, '__toString')) {
                return (string) $model;
            }

            if (isset($model->id)) {
                return (string) $model->id;
            }

            if ($staticFallback) {
                return $staticFallback;
            }

            return class_basename($model);
        }

        if ($staticFallback) {
            return $staticFallback;
        }

        return is_scalar($model) ? (string) $model : 'Unknown';
    }
}
