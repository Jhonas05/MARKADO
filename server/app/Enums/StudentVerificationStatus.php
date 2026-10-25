<?php

namespace App\Enums;

enum StudentVerificationStatus: string
{
    case Unverified = 'unverified';
    case Verified = 'verified';
    case Disputed = 'disputed';
}
