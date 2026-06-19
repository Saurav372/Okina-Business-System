<?php

namespace App\Contracts;

interface AuditEventContract
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
