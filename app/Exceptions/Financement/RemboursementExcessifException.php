<?php

namespace App\Exceptions\Financement;

use App\Models\Financement;
use RuntimeException;

class RemboursementExcessifException extends RuntimeException
{
    public function __construct(
        public readonly Financement $financement,
        public readonly float $montantRemboursement,
    ) {
        parent::__construct(sprintf(
            'Le remboursement (%s) dépasse le montant restant dû (%s) pour ce financement.',
            number_format($montantRemboursement, 2, ',', ' '),
            number_format((float) $financement->montant_restant, 2, ',', ' '),
        ));
    }
}
