<?php

namespace App\Identity\Contracts;

use App\Identity\Data\InstitutionalEmailVerificationMessage;

interface InstitutionalEmailVerificationDelivery
{
    public function deliver(InstitutionalEmailVerificationMessage $message): bool;
}
