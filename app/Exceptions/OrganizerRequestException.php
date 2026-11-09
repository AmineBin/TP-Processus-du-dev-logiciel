<?php

namespace App\Exceptions;

use App\Support\ErrorHandler;
use RuntimeException;

/** Conflit métier contrôlé, distinct des erreurs SQL et des réponses HTTP. */
class OrganizerRequestException extends RuntimeException
{
    /** Transporte un code public stable et le statut HTTP adapté. */
    public function __construct(public readonly string $errorCode, public readonly int $httpStatus = 409)
    {
        parent::__construct(ErrorHandler::message($errorCode));
    }
}
