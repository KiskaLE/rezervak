import naja from 'naja';
import netteForms from 'nette-forms';
import Chart from 'chart.js/auto';


window.Nette = netteForms;
window.Naja = naja;
window.Chart = Chart

document.addEventListener('DOMContentLoaded', naja.initialize.bind(naja));


netteForms.initOnLoad();
