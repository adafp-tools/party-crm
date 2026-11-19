import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import jsQR from 'jsqr';
import Chart from 'chart.js/auto';

window.jsQR = jsQR;
window.Chart = Chart;

// Livewire auto-starts Alpine on DOMContentLoaded when inject_assets is false
