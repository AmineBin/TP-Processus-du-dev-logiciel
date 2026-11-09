<span @class([
    'registration-state',
    'registration-state-confirmed' => $inscription->status?->code === \App\Models\RegistrationStatus::CONFIRMED,
    'registration-state-pending' => $inscription->status?->code === \App\Models\RegistrationStatus::PENDING,
    'registration-state-cancelled' => $inscription->isCancelled(),
])>{{ $inscription->status?->libelle ?? 'Statut inconnu' }}</span>
