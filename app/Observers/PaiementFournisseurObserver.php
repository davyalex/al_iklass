<?php

namespace App\Observers;

use App\Models\PaiementFournisseur;
use Illuminate\Support\Facades\Cache;

class PaiementFournisseurObserver
{
    public function saved(PaiementFournisseur $paiement): void
    {
        Cache::forget('stock:kpi:'.$paiement->date_paiement->format('Y-m'));
    }

    public function deleted(PaiementFournisseur $paiement): void
    {
        Cache::forget('stock:kpi:'.$paiement->date_paiement->format('Y-m'));
    }
}
