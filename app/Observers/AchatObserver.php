<?php

namespace App\Observers;

use App\Models\Achat;
use Illuminate\Support\Facades\Cache;

class AchatObserver
{
    public function saved(Achat $achat): void
    {
        Cache::forget('stock:kpi:'.$achat->date_achat->format('Y-m'));
    }

    public function deleted(Achat $achat): void
    {
        Cache::forget('stock:kpi:'.$achat->date_achat->format('Y-m'));
    }
}
