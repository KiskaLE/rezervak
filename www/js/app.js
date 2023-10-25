import naja from 'naja';
import netteForms from 'nette-forms';

window.Nette = netteForms;
window.Naja = naja;

document.addEventListener('DOMContentLoaded', naja.initialize.bind(naja));


netteForms.initOnLoad();
