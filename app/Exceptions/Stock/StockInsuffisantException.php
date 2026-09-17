<?php

namespace App\Exceptions\Stock;

use App\Models\Article;
use RuntimeException;

class StockInsuffisantException extends RuntimeException
{
    public function __construct(
        public readonly Article $article,
        public readonly int $quantiteDemandee,
    ) {
        parent::__construct(sprintf(
            "Stock insuffisant pour l'article « %s » : demandé %d, disponible %d.",
            $article->nom,
            $quantiteDemandee,
            $article->quantite_stock,
        ));
    }
}
