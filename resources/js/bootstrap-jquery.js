import $ from 'jquery';

// Doit s'exécuter — et se terminer — avant l'import de tout plugin jQuery
// (select2, datatables.net...) : ces paquets UMD cherchent `window.jQuery`
// au moment de leur propre évaluation, qui précède toujours le code de
// niveau supérieur d'app.js à cause du hoisting des imports ES modules.
window.$ = window.jQuery = $;

export default $;
