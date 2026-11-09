<?php

namespace App\Actions\OrganizerRequests;

use App\Exceptions\OrganizerRequestException;
use App\Models\OrganizerRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\ErrorHandler;
use Illuminate\Support\Facades\DB;

/** Refus tracé, sans création de compte ni conservation d’un hachage devenu inutile. */
class RejectOrganizerRequest
{
    /** Le motif est déjà validé par la Request ; le verrou empêche un double traitement. */
    public function handle(string $id, User $admin, string $reason): OrganizerRequest
    {
        if (! $admin->est_actif || ! $admin->isAdmin()) {
            throw new OrganizerRequestException(ErrorHandler::ADMIN_REQUIRED, 403);
        }

        return DB::transaction(function () use ($id, $admin, $reason): OrganizerRequest {
            $application = OrganizerRequest::query()->lockForUpdate()->findOrFail($id);
            if ($application->statut !== OrganizerRequest::PENDING) {
                throw new OrganizerRequestException(ErrorHandler::ORGANIZER_REQUEST_ALREADY_PROCESSED);
            }
            if ($application->requestedRole?->code !== Role::ORGANIZER_CODE) {
                throw new OrganizerRequestException(ErrorHandler::ORGANIZER_REQUEST_INVALID_ROLE);
            }

            $application->statut = OrganizerRequest::REJECTED;
            $application->date_traitement = now();
            $application->reviewer()->associate($admin);
            $application->motif_refus = $reason;
            $application->mot_de_passe_hash = '';
            $application->save();

            return $application->loadMissing(['requestedRole', 'reviewer']);
        });
    }
}
