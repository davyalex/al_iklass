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
