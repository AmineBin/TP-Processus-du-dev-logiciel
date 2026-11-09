<?php

namespace App\Http\Controllers\Admin;

use App\Actions\OrganizerRequests\ApproveOrganizerRequest;
use App\Actions\OrganizerRequests\RejectOrganizerRequest as RejectAction;
use App\Exceptions\OrganizerRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListOrganizerRequestsRequest;
use App\Http\Requests\RejectOrganizerRequest;
use App\Http\Resources\OrganizerRequestResource;
use App\Models\OrganizerRequest;
use App\Support\ErrorHandler;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Liste et traitement réservés aux administrateurs actifs, sans logique dans les routes. */
class OrganizerRequestController extends Controller
{
    /** File en attente par défaut ; le filtre permet également de consulter l’historique. */
    public function index(ListOrganizerRequestsRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $applications = OrganizerRequest::forOrganizers()->with(['requestedRole', 'reviewer'])
            ->where('statut', $data['status'] ?? OrganizerRequest::PENDING)
            ->orderByDesc('date_creation')->orderByDesc('id')
            ->paginate($data['per_page'] ?? 20)->withQueryString();

        return OrganizerRequestResource::collection($applications);
    }

    /** Détail d’une demande organisateur seulement ; aucun identifiant secret n’est sérialisé. */
    public function show(string $id): OrganizerRequestResource
    {
        return new OrganizerRequestResource(OrganizerRequest::forOrganizers()
            ->with(['requestedRole', 'reviewer'])->findOrFail($id));
    }

    /** Autorise la création du compte et conserve la trace de l’administrateur. */
    public function approve(Request $request, string $id, ApproveOrganizerRequest $approve): JsonResponse
    {
        return $this->process($request, fn () => $approve->handle($id, $request->user()), 'Demande approuvée, compte organisateur créé.');
    }

    /** Refuse sans créer de compte, avec le motif fourni et validé. */
    public function reject(RejectOrganizerRequest $request, string $id, RejectAction $reject): JsonResponse
    {
        return $this->process($request, fn () => $reject->handle($id, $request->user(), $request->validated('reason')), 'Demande refusée.');
    }

    /** Adapte les conflits métier et les erreurs SQL au contrat JSON public. */
    private function process(Request $request, Closure $action, string $message): JsonResponse
    {
        try {
            $application = $action();

            return (new OrganizerRequestResource($application->loadMissing(['requestedRole', 'reviewer'])))
                ->additional(['message' => $message])->response();
        } catch (OrganizerRequestException $exception) {
            return ErrorHandler::respond($request, $exception->errorCode, $exception->httpStatus);
        } catch (UniqueConstraintViolationException $exception) {
            // Une création de compte concurrente peut intervenir après les vérifications métier.
            return ErrorHandler::respond($request, ErrorHandler::ORGANIZER_REQUEST_ACCOUNT_CONFLICT, 409);
        } catch (QueryException $exception) {
            report($exception);

            return ErrorHandler::respond($request, ErrorHandler::ORGANIZER_REQUEST_FAILED, 503);
        }
    }
}
