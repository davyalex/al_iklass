<?php

namespace App\Support;

class Money
{
    /**
     * Formate un montant : 2 décimales uniquement si elles sont significatives
     * (ex: 1500 -> "1 500", 1500.5 -> "1 500,50").
     */
    public static function format(float|string|int|null $valeur): string
    {
        $arrondi = round((float) $valeur, 2);

        $decimales = abs($arrondi - floor($arrondi)) > 0.0 ? 2 : 0;

        return number_format($arrondi, $decimales, ',', ' ');
    }
}
