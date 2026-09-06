<?php

namespace App\Contracts;

interface CustomizationOptionContract
{
    public function product(string $slug): ?array;

    public function validateSelection(string $slug, array $selection): array;

    public function guidance(): array;
}
