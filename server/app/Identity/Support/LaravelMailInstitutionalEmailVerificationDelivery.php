<?php

namespace App\Identity\Support;

use App\Identity\Contracts\InstitutionalEmailVerificationDelivery;
use App\Identity\Data\InstitutionalEmailVerificationMessage;
use App\Mail\InstitutionalEmailVerificationMail;
use Illuminate\Support\Facades\Mail;

final class LaravelMailInstitutionalEmailVerificationDelivery implements InstitutionalEmailVerificationDelivery
{
    public function deliver(InstitutionalEmailVerificationMessage $message): bool
    {
        Mail::to($message->recipient)->send(new InstitutionalEmailVerificationMail(
            $message->token,
            $message->expiresAt,
        ));

        return true;
    }
}
