<?php

namespace App\Identity\Support;

use App\Identity\Contracts\InstitutionalEmailVerificationDelivery;
use App\Identity\Data\InstitutionalEmailVerificationMessage;

final class UnavailableInstitutionalEmailVerificationDelivery implements InstitutionalEmailVerificationDelivery
{
    public function deliver(InstitutionalEmailVerificationMessage $message): bool
    {
        return false;
    }
}
