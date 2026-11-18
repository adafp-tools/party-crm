import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import jsQR from 'jsqr';

// Make jsQR globally available for the QR scanner Alpine component
window.jsQR = jsQR;

Livewire.start();
