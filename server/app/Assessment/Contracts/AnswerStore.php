<?php

namespace App\Assessment\Contracts;

use App\Assessment\Data\AnswerSaveAcknowledgement;
use App\Assessment\Data\AnswerSaveCommand;

interface AnswerStore
{
    public function save(AnswerSaveCommand $command): AnswerSaveAcknowledgement;
}
