<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizerRequest;
use App\Models\OrganizerRequest;
use App\Models\Role;
use App\Support\ErrorHandler;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Dépôt public d’une préinscription, sans compte actif ni connexion automatique. */
class OrganizerRequestController extends Controller
{
    /** Hache le secret dès réception et fixe le rôle demandé côté serveur. */
    public function store(StoreOrganizerRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $role = Role::where('code', Role::ORGANIZER_CODE)->first();
            if (! $role) {
                return ErrorHandler::respond($request, ErrorHandler::ORGANIZER_REQUEST_ROLE_UNAVAILABLE, 503);
            }

            $application = DB::transaction(function () use ($data, $role): OrganizerRequest {
                $application = new OrganizerRequest([
                    'nom_utilisateur' => $data['username'],
                    'email' => $data['email'],
                    'mot_de_passe_hash' => Hash::make($data['password']),
                    'nom' => $data['last_name'] ?? null,
                    'prenom' => $data['first_name'] ?? null,
                    'date_naissance' => $data['birth_date'],
                    'numero_telephone' => $data['phone'],
                ]);
                $application->requestedRole()->associate($role);
                $application->statut = OrganizerRequest::PENDING;
                $application->save();

                return $application;
            });
        } catch (UniqueConstraintViolationException $exception) {
            return ErrorHandler::respond($request, ErrorHandler::ORGANIZER_REQUEST_CONFLICT, 409);
        } catch (QueryException $exception) {
            report($exception);

            return ErrorHandler::respond($request, ErrorHandler::ORGANIZER_REQUEST_FAILED, 503);
        }

        return response()->json([
            'message' => 'Demande enregistrée, en attente de validation par un administrateur.',
            'data' => ['id' => $application->id, 'status' => $application->statut],
        ], 201);
    }
}
