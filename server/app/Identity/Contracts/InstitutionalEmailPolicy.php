<?php

namespace App\Identity\Contracts;

interface InstitutionalEmailPolicy
{
    public function normalizeAllowedAddress(string $email): ?string;
}
