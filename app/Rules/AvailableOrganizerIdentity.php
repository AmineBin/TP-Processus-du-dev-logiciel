<?php

namespace App\Rules;

use App\Models\OrganizerRequest;
use App\Models\User;
use App\Support\ErrorHandler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/** Vérifie une identité dans les comptes et la file d’attente, sans dépendre de la casse. */
class AvailableOrganizerIdentity implements ValidationRule
{
    /** Le nom SQL provient exclusivement de ces deux valeurs fixées par le code. */
    public function __construct(private readonly string $column)
    {
        if (! in_array($column, ['email', 'nom_utilisateur'], true)) {
            throw new InvalidArgumentException('Colonne d’identité non autorisée.');
        }
    }

    /** Refuse un compte existant ou une demande encore en attente, mais pas un ancien refus. */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }
        $normalized = mb_strtolower($value);
        $taken = User::query()->whereRaw('LOWER('.$this->column.') = ?', [$normalized])->exists()
            || OrganizerRequest::pending()->whereRaw('LOWER('.$this->column.') = ?', [$normalized])->exists();

        if ($taken) {
            $fail(ErrorHandler::message($this->column === 'email' ? 'EMAIL_TAKEN' : 'USERNAME_TAKEN'));
        }
    }
}
