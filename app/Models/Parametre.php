<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametre extends Model
{
    protected $fillable = ['cle', 'valeur', 'libelle', 'groupe', 'ordre'];

    public static function valeur(string $cle, ?string $defaut = null): ?string
    {
        return static::where('cle', $cle)->value('valeur') ?? $defaut;
    }

    public static function definir(string $cle, string $valeur): void
    {
        static::where('cle', $cle)->update(['valeur' => $valeur]);
    }
}
