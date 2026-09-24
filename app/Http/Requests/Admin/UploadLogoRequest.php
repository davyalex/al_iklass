<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('parametres.gerer');
    }

    public function rules(): array
    {
        return [
            // 'image' seul accepte le SVG par défaut (script embarquable, XSS
            // stocké) : on restreint explicitement aux formats matriciels.
            // Bornes de dimensions : en dessous les icônes seraient floues,
            // au-dessus le traitement GD consommerait trop de mémoire.
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=4000,max_height=4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.mimes' => 'Le logo doit être une image PNG, JPG ou WebP (PNG à fond transparent recommandé).',
            'logo.dimensions' => 'Le logo doit mesurer entre 64 et 4000 pixels de côté (512×512 recommandé).',
        ];
    }
}
