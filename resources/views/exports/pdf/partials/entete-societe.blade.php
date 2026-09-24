{{-- Bandeau d'en-tête commun à tous les PDF : logo et nom paramétrés
     (dompdf : mise en page en tableau, logo en data URI). --}}
@inject('identiteApplication', \App\Services\Admin\IdentiteApplicationService::class)

<table style="width: 100%; border-collapse: collapse; margin: 0 0 14px; border: 0; border-bottom: 2px solid #073763;">
    <tr>
        @if ($logoPdf = $identiteApplication->logoPdfDataUri())
            <td style="width: 1%; border: 0; padding: 0 12px 8px 0; vertical-align: middle; white-space: nowrap;">
                <img src="{{ $logoPdf }}" alt="" style="max-height: 44px; max-width: 160px;">
            </td>
        @endif
        <td style="border: 0; padding: 0 0 8px; vertical-align: middle; background: none;">
            <div style="font-size: 15px; font-weight: bold; color: #073763;">{{ $identiteApplication->nom() }}</div>
        </td>
    </tr>
</table>
