<?php

namespace App\Observers;

use App\Models\MouvementStock;
use Illuminate\Support\Facades\Cache;

class MouvementStockObserver
{
    public function saved(MouvementStock $mouvement): void
    {
        Cache::forget('stock:kpi:'.$mouvement->date_mouvement->format('Y-m'));
    }

    public function deleted(MouvementStock $mouvement): void
    {
        Cache::forget('stock:kpi:'.$mouvement->date_mouvement->format('Y-m'));
    }
}
