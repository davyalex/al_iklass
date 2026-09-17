<?php

namespace App\Exceptions\Stock;

use App\Models\Achat;
use RuntimeException;

class TropPercuException extends RuntimeException
{
    public function __construct(
        public readonly Achat $achat,
        public readonly float $montantPaiement,
    ) {
        parent::__construct(sprintf(
            'Le paiement (%s) dépasse le montant restant dû (%s) pour cet achat.',
            number_format($montantPaiement, 2, ',', ' '),
            number_format((float) $achat->montant_restant, 2, ',', ' '),
        ));
    }
}
