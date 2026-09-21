<style>
    .bg-arret { background-color: #6f42c1; }
    .bg-arret-subtle { background-color: #ece4f9; }
    .border-arret { border-color: #6f42c1 !important; }
    .chip-vehicule, .chip-vehicule-gestion { width: 88px; }
    .chip-vehicule.statut-en_circulation, .chip-vehicule-gestion.statut-en_circulation { border-color: #198754 !important; }
    .chip-vehicule.statut-depannage, .chip-vehicule-gestion.statut-depannage { border-color: #dc3545 !important; }
    .chip-vehicule.statut-maintenance, .chip-vehicule-gestion.statut-maintenance { border-color: #ffc107 !important; }
    .chip-vehicule.statut-arret, .chip-vehicule-gestion.statut-arret { border-color: #6f42c1 !important; }

    .point-statut { width: 14px; height: 14px; }
    .point-statut.statut-en_circulation::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background-color: inherit;
        animation: pulse-signal 1.6s cubic-bezier(0, 0, 0.2, 1) infinite;
    }
    @keyframes pulse-signal {
        0% { transform: scale(1); opacity: .6; }
        100% { transform: scale(2.4); opacity: 0; }
    }
</style>
