<?php

namespace App\Actions\OrganizerRequests;

use App\Exceptions\OrganizerRequestException;
use App\Models\OrganizerRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\ErrorHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Création atomique du compte et traitement de la demande, après autorisation admin. */
class ApproveOrganizerRequest
{
    /** Refuse un second traitement, un rôle non organisateur ou une identité déjà utilisée. */
    public function handle(string $id, User $admin): OrganizerRequest
    {
        if (! $admin->est_actif || ! $admin->isAdmin()) {
            throw new OrganizerRequestException(ErrorHandler::ADMIN_REQUIRED, 403);
        }

        return DB::transaction(function () use ($id, $admin): OrganizerRequest {
            $application = OrganizerRequest::query()->lockForUpdate()->findOrFail($id);
            if ($application->statut !== OrganizerRequest::PENDING) {
                throw new OrganizerRequestException(ErrorHandler::ORGANIZER_REQUEST_ALREADY_PROCESSED);
            }

            $role = Role::where('code', Role::ORGANIZER_CODE)->first();
            if (! $role) {
                throw new OrganizerRequestException(ErrorHandler::ORGANIZER_REQUEST_ROLE_UNAVAILABLE, 503);
            }
            if ($application->role_id !== $role->id) {
                throw new OrganizerRequestException(ErrorHandler::ORGANIZER_REQUEST_INVALID_ROLE);
            }

            $conflict = User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($application->email)])
                ->orWhereRaw('LOWER(nom_utilisateur) = ?', [mb_strtolower($application->nom_utilisateur)])->exists();
            if ($conflict) {
                throw new OrganizerRequestException(ErrorHandler::ORGANIZER_REQUEST_ACCOUNT_CONFLICT);
            }
            if (! $application->date_naissance || $application->date_naissance->isFuture()
                || ! $application->numero_telephone || ! Hash::isHashed($application->mot_de_passe_hash)) {
                throw new OrganizerRequestException(ErrorHandler::ORGANIZER_REQUEST_INCOMPLETE);
            }

            $user = new User([
                'nom_utilisateur' => $application->nom_utilisateur,
                'email' => $application->email,
                // Déjà haché au dépôt : ne jamais appliquer Hash::make() une seconde fois.
                'mot_de_passe_hash' => $application->mot_de_passe_hash,
                'prenom' => $application->prenom,
                'nom' => $application->nom,
                'date_naissance' => $application->date_naissance,
                'numero_telephone' => $application->numero_telephone,
            ]);
            $user->role()->associate($role);
            $user->save();

            $application->statut = OrganizerRequest::APPROVED;
            $application->date_traitement = now();
            $application->reviewer()->associate($admin);
            $application->createdUser()->associate($user);
            $application->motif_refus = null;
            // L’historique administratif n’a plus besoin de conserver une copie du hachage.
            $application->mot_de_passe_hash = '';
            $application->save();

            return $application->loadMissing(['requestedRole', 'reviewer']);
        });
    }
}
