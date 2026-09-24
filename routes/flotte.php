<?php

use App\Http\Controllers\Flotte\DetteController;
use App\Http\Controllers\Flotte\EtatParcController;
use App\Http\Controllers\Flotte\GestionnaireController;
use App\Http\Controllers\Flotte\HistoriqueMecanicienController;
use App\Http\Controllers\Flotte\InterventionController;
use App\Http\Controllers\Flotte\OperationProgrammeeController;
use App\Http\Controllers\Flotte\TypeOperationController;
use App\Http\Controllers\Flotte\TypePanneController;
use App\Http\Controllers\Flotte\VehiculeController;
use App\Http\Controllers\Flotte\VehiculeRapportController;
use App\Http\Controllers\Flotte\VersementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'reinitialiser.statut.journalier'])->prefix('flotte')->name('flotte.')->group(function () {
    Route::middleware('permission:flotte.vehicule.voir|flotte.vehicule.voir_affectes|flotte.vehicule.remise_circulation')->group(function () {
        Route::get('vehicules', [VehiculeController::class, 'index'])->name('vehicules.index');
        Route::get('vehicules/{vehicule}/historique', [VehiculeController::class, 'historique'])->name('vehicules.historique');
        Route::get('vehicules/{vehicule}/rapport', [VehiculeRapportController::class, 'index'])->name('vehicules.rapport');
        Route::get('vehicules/{vehicule}/rapport/statuts', [VehiculeRapportController::class, 'statuts'])->name('vehicules.rapport.statuts');
        Route::get('vehicules/{vehicule}/rapport/sorties', [VehiculeRapportController::class, 'sorties'])->name('vehicules.rapport.sorties');
        Route::get('vehicules/{vehicule}', [VehiculeController::class, 'show'])->name('vehicules.show');

        Route::get('etat-parc', [EtatParcController::class, 'index'])->name('etat-parc.index');
    });

    Route::middleware('permission:flotte.vehicule.voir')->group(function () {
        Route::get('gestionnaires', [GestionnaireController::class, 'index'])->name('gestionnaires.index');
        Route::get('gestionnaires/{gestionnaire}/compte', [GestionnaireController::class, 'compte'])->name('gestionnaires.compte');
    });

    // Un gestionnaire (flotte.versement.gerer) consulte et enregistre
    // uniquement ses propres versements ; flotte.vehicule.voir/gerer donne
    // la vue et la création pour tous (admin).
    Route::middleware('permission:flotte.vehicule.voir|flotte.versement.gerer')->group(function () {
        Route::get('versements', [VersementController::class, 'index'])->name('versements.index');
        Route::get('versements/kpis', [VersementController::class, 'kpis'])->name('versements.kpis');
        Route::get('versements/data', [VersementController::class, 'data'])->name('versements.data');
        Route::get('versements/export/excel', [VersementController::class, 'exportExcel'])->name('versements.export.excel');
        Route::get('versements/export/pdf', [VersementController::class, 'exportPdf'])->name('versements.export.pdf');
    });

    Route::middleware('permission:flotte.vehicule.gerer|flotte.versement.gerer')->group(function () {
        Route::post('versements', [VersementController::class, 'store'])->name('versements.store');
    });

    Route::middleware('permission:flotte.vehicule.gerer')->group(function () {
        Route::post('vehicules', [VehiculeController::class, 'store'])->name('vehicules.store');
        Route::put('vehicules/{vehicule}', [VehiculeController::class, 'update'])->name('vehicules.update');
        Route::delete('vehicules/{vehicule}', [VehiculeController::class, 'destroy'])->name('vehicules.destroy');
    });

    Route::middleware('permission:flotte.vehicule.gerer|flotte.vehicule.statut.gerer')->group(function () {
        Route::patch('vehicules/{vehicule}/statut', [VehiculeController::class, 'changerStatut'])->name('vehicules.statut');
    });

    Route::middleware('permission:flotte.vehicule.gerer|flotte.vehicule.remise_circulation')->group(function () {
        Route::post('vehicules/{vehicule}/remise-circulation', [VehiculeController::class, 'remiseEnCirculation'])->name('vehicules.remise-circulation');
    });

    Route::middleware('permission:flotte.dette.gerer')->group(function () {
        Route::post('gestionnaires/{gestionnaire}/dette/annuler', [GestionnaireController::class, 'annulerDette'])->name('gestionnaires.dette.annuler');
    });

    // Page dette : un admin (flotte.dette.gerer) voit tous les gestionnaires,
    // un gestionnaire (flotte.dette.regler seul) ne voit et ne règle que sa
    // propre dette (contrôlé dans DetteController/ReglerDetteRequest).
    Route::middleware('permission:flotte.dette.gerer|flotte.dette.regler')->group(function () {
        Route::get('dettes', [DetteController::class, 'index'])->name('dettes.index');
        Route::get('dettes/kpis', [DetteController::class, 'kpis'])->name('dettes.kpis');
        Route::get('gestionnaires/{gestionnaire}/dette/detail', [DetteController::class, 'detail'])->name('gestionnaires.dette.detail');
        Route::post('gestionnaires/{gestionnaire}/dette/regler', [DetteController::class, 'regler'])->name('gestionnaires.dette.regler');
    });

    // Opérations programmées : voir (admin/gestionnaire_stock/chef_mecanicien),
    // planifier (admin/gestionnaire_stock), réaliser/clôturer (admin/chef_mecanicien).
    Route::middleware('permission:operations.voir')->group(function () {
        Route::get('operations', [OperationProgrammeeController::class, 'index'])->name('operations.index');
        Route::get('operations/vehicules/{vehicule}/detail', [OperationProgrammeeController::class, 'detail'])->name('operations.detail');
        Route::get('operations/historique', [OperationProgrammeeController::class, 'historique'])->name('operations.historique.index');
        Route::get('operations/historique/data', [OperationProgrammeeController::class, 'historiqueData'])->name('operations.historique.data');
        Route::get('operations/historique/export/excel', [OperationProgrammeeController::class, 'historiqueExportExcel'])->name('operations.historique.export.excel');
        Route::get('operations/historique/export/pdf', [OperationProgrammeeController::class, 'historiqueExportPdf'])->name('operations.historique.export.pdf');
    });

    Route::middleware('permission:operations.gerer')->group(function () {
        Route::post('operations', [OperationProgrammeeController::class, 'store'])->name('operations.store');
        Route::put('operations/{operation}', [OperationProgrammeeController::class, 'update'])->name('operations.update');
    });

    Route::middleware('permission:operations.realiser')->group(function () {
        Route::post('operations/{operation}/realiser', [OperationProgrammeeController::class, 'realiser'])->name('operations.realiser');
    });

    // Référentiel des types d'opération programmée (vidange, assurance...).
    Route::middleware('permission:operations.type.gerer')->group(function () {
        Route::post('operations/types', [TypeOperationController::class, 'store'])->name('operations.types.store');
        Route::put('operations/types/{type}', [TypeOperationController::class, 'update'])->name('operations.types.update');
    });

    // Interventions (pannes/réparations) : voir (admin/chef_mecanicien),
    // déclarer/clôturer (chef_mecanicien) — la clôture passe par
    // vehicules.remise-circulation existant, pas par une route dédiée ici.
    Route::middleware('permission:interventions.voir')->group(function () {
        Route::get('interventions', [InterventionController::class, 'index'])->name('interventions.index');
        Route::get('interventions/vehicules/{vehicule}/detail', [InterventionController::class, 'detail'])->name('interventions.detail');
        Route::get('interventions/historique', [InterventionController::class, 'historique'])->name('interventions.historique.index');
        Route::get('interventions/historique/data', [InterventionController::class, 'historiqueData'])->name('interventions.historique.data');
        Route::get('interventions/historique/export/excel', [InterventionController::class, 'historiqueExportExcel'])->name('interventions.historique.export.excel');
        Route::get('interventions/historique/export/pdf', [InterventionController::class, 'historiqueExportPdf'])->name('interventions.historique.export.pdf');
    });

    Route::middleware('permission:interventions.declarer')->group(function () {
        Route::post('interventions', [InterventionController::class, 'store'])->name('interventions.store');
    });

    // Référentiel des types de panne.
    Route::middleware('permission:interventions.type.gerer')->group(function () {
        Route::post('interventions/types', [TypePanneController::class, 'store'])->name('interventions.types.store');
        Route::put('interventions/types/{type}', [TypePanneController::class, 'update'])->name('interventions.types.update');
    });

    // Historique personnel d'un chef mécanicien (ses changements de statut,
    // tous véhicules confondus).
    Route::middleware('permission:flotte.vehicule.remise_circulation')->group(function () {
        Route::get('mon-historique', [HistoriqueMecanicienController::class, 'index'])->name('mon-historique.index');
        Route::get('mon-historique/kpis', [HistoriqueMecanicienController::class, 'kpis'])->name('mon-historique.kpis');
        Route::get('mon-historique/statuts', [HistoriqueMecanicienController::class, 'statuts'])->name('mon-historique.statuts');
    });
});
