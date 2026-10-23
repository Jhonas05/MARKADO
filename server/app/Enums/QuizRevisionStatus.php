<?php

namespace App\Enums;

enum QuizRevisionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
