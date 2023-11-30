import naja from 'naja';
import netteForms from 'nette-forms';
import Chart from 'chart.js/auto';
import Moment from 'moment';



window.Nette = netteForms;
window.Naja = naja;
window.Chart = Chart;
window.Moment = Moment;

document.addEventListener('DOMContentLoaded', naja.initialize.bind(naja));


netteForms.initOnLoad();
