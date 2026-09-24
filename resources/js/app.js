// Doit s'exécuter — et se terminer — avant l'import de tout plugin jQuery
// (datatables.net...) : ces paquets UMD cherchent `window.jQuery` au moment
// de leur propre évaluation, qui précède toujours le code de niveau
// supérieur de ce fichier à cause du hoisting des imports ES modules.
//
// select2 n'est PAS importé ici : c'est un bundle UMD sans build ESM propre,
// et son interop CommonJS via Rollup/Rolldown échoue à s'attacher au bon
// objet jQuery (bug constaté : $.fn.select2 reste undefined même une fois
// jQuery correctement exposé). Il est donc chargé en <script> classique
// (voir resources/views/layouts/app.blade.php et guest.blade.php), après
// ce bundle, ce qui le fait fonctionner exactement comme prévu par son
// propre code UMD (branche "jQuery global").
import './bootstrap-jquery';

import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

import Swal from 'sweetalert2';

window.Swal = Swal;

import 'datatables.net-bs5';

import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);
window.Chart = Chart;

// Pagination DataTables plus compacte sur smartphone (moins de numéros de page).
$.fn.dataTable.ext.pager.numbers_length = window.matchMedia('(max-width: 575.98px)').matches ? 5 : 7;

/**
 * Tableaux de liste en « cartes » sur smartphone : chaque cellule reçoit en
 * `data-label` le libellé de sa colonne, que le CSS affiche devant la valeur
 * quand le tableau est empilé (voir .al-table-cartes dans app.scss).
 * Concerne les tableaux de liste (.table-hover hors .table-sm) ; un tableau
 * peut s'en exclure avec la classe .al-table-sans-cartes.
 */
const SELECTEUR_TABLE_CARTES = '.table-responsive table.table-hover:not(.table-sm):not(.al-table-sans-cartes)';

// Montants (« 130 000 FCFA »), nombres, dates et références (ACH-2026-0001) : jamais coupés.
const VALEUR_INSECABLE = /^(-?[\d\s.,]+(\s?FCFA)?|\d{2}\/\d{2}\/\d{4}(\s\d{2}:\d{2})?|[A-Z]{2,}(-[A-Z0-9]+)+)$/;

function etiqueterCellules(table) {
    const libelles = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim());

    table.classList.add('al-table-cartes');
    table.querySelectorAll('tbody tr').forEach((ligne) => {
        let indexColonne = 0;
        Array.from(ligne.children).forEach((cellule) => {
            cellule.setAttribute('data-label', libelles[indexColonne] ?? '');
            if (VALEUR_INSECABLE.test(cellule.textContent.trim())) {
                cellule.classList.add('text-nowrap');
            }
            indexColonne += cellule.colSpan || 1;
        });
    });
}

$(document).on('draw.dt', (e) => {
    if (e.target.matches?.(SELECTEUR_TABLE_CARTES)) {
        etiqueterCellules(e.target);
    }
});

/**
 * Cartes de filtre repliables sur smartphone : un bouton « Filtres » replie
 * les champs pour laisser la place aux données. La carte reste ouverte si un
 * filtre est actif au chargement, et un indicateur signale les filtres actifs
 * (déduit de la visibilité du bouton « réinitialiser » que gère chaque page).
 */
function initialiserFiltresRepliables() {
    document.querySelectorAll('.al-filtres').forEach((carte, index) => {
        const corps = carte.querySelector(':scope > .card-body');
        if (!corps) {
            return;
        }

        corps.id ||= `al-filtres-corps-${index}`;
        const bouton = document.createElement('button');
        bouton.type = 'button';
        bouton.className = 'al-filtres-toggle';
        bouton.setAttribute('aria-controls', corps.id);
        bouton.innerHTML = '<span><i class="bi bi-funnel me-2"></i>Filtres<span class="al-filtres-indicateur d-none">actifs</span></span><i class="bi bi-chevron-down al-filtres-chevron"></i>';
        carte.prepend(bouton);
        carte.classList.add('al-filtres-repliable');

        const indicateur = bouton.querySelector('.al-filtres-indicateur');
        const filtreActif = () => {
            const boutonReset = carte.querySelector('.al-btn-reset');

            return !!boutonReset && !boutonReset.classList.contains('d-none');
        };
        const ouvrir = (estOuverte) => {
            carte.classList.toggle('is-ouverte', estOuverte);
            bouton.setAttribute('aria-expanded', String(estOuverte));
        };

        ouvrir(filtreActif());
        bouton.addEventListener('click', () => ouvrir(!carte.classList.contains('is-ouverte')));

        const majIndicateur = () => indicateur.classList.toggle('d-none', !filtreActif());
        majIndicateur();
        const boutonReset = carte.querySelector('.al-btn-reset');
        if (boutonReset) {
            new MutationObserver(majIndicateur).observe(boutonReset, { attributes: true, attributeFilter: ['class'] });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(SELECTEUR_TABLE_CARTES).forEach(etiqueterCellules);
    initialiserFiltresRepliables();
});

/**
 * Sidebar : réduction (desktop, mémorisée dans le navigateur), infobulles des
 * liens en mode réduit, lien actif ramené dans la zone visible et fondus
 * haut/bas signalant les liens masqués par le défilement.
 */
function initialiserSidebar() {
    const racine = document.documentElement;
    const boutonReduire = document.getElementById('btn-toggle-sidebar');
    const infobulles = Array.from(document.querySelectorAll('.al-sidebar-desktop .nav-link')).map((lien) => new bootstrap.Tooltip(lien, {
        title: lien.dataset.alLabel,
        placement: 'right',
        trigger: 'hover focus',
        container: 'body',
        customClass: 'al-sidebar-tooltip',
    }));

    function appliquerEtat(estReduite) {
        racine.classList.toggle('al-sidebar-collapsed', estReduite);
        infobulles.forEach((infobulle) => {
            if (estReduite) {
                infobulle.enable();
            } else {
                infobulle.hide();
                infobulle.disable();
            }
        });

        if (boutonReduire) {
            const libelle = estReduite ? 'Agrandir le menu' : 'Réduire le menu';
            boutonReduire.setAttribute('aria-expanded', String(!estReduite));
            boutonReduire.setAttribute('aria-label', libelle);
            boutonReduire.setAttribute('title', libelle);
        }
    }

    appliquerEtat(racine.classList.contains('al-sidebar-collapsed'));

    boutonReduire?.addEventListener('click', () => {
        const estReduite = !racine.classList.contains('al-sidebar-collapsed');
        appliquerEtat(estReduite);
        try {
            localStorage.setItem('al.sidebar', estReduite ? 'collapsed' : 'expanded');
        } catch (e) {
            // Stockage indisponible (navigation privée...) : l'état n'est simplement pas mémorisé.
        }
    });

    document.querySelectorAll('.al-sidebar-scroll').forEach((zone) => {
        const mettreAJourFondus = () => {
            zone.classList.toggle('al-fade-top', zone.scrollTop > 4);
            zone.classList.toggle('al-fade-bottom', zone.scrollTop + zone.clientHeight < zone.scrollHeight - 4);
        };
        const centrerLienActif = () => {
            const lienActif = zone.querySelector('.nav-link.active');
            if (lienActif && (lienActif.offsetTop < zone.scrollTop || lienActif.offsetTop + lienActif.offsetHeight > zone.scrollTop + zone.clientHeight)) {
                zone.scrollTop = lienActif.offsetTop - (zone.clientHeight - lienActif.offsetHeight) / 2;
            }
            mettreAJourFondus();
        };

        zone.addEventListener('scroll', mettreAJourFondus, { passive: true });
        window.addEventListener('resize', mettreAJourFondus);
        zone.closest('.offcanvas')?.addEventListener('shown.bs.offcanvas', centrerLienActif);
        centrerLienActif();
    });
}

document.addEventListener('DOMContentLoaded', initialiserSidebar);

/**
 * Formate un montant : 2 décimales uniquement si elles sont significatives
 * (ex: 1500 -> "1 500", 1500.5 -> "1 500,50"). Miroir JS de App\Support\Money::format().
 */
window.formatMontant = function (valeur) {
    const arrondi = Math.round((Number(valeur) || 0) * 100) / 100;
    const aDesDecimales = arrondi !== Math.floor(arrondi);

    return arrondi.toLocaleString('fr-FR', {
        minimumFractionDigits: aDesDecimales ? 2 : 0,
        maximumFractionDigits: aDesDecimales ? 2 : 0,
    });
};
