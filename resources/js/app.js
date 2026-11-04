import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Livewire bundles Alpine and starts it together with itself, so there is a
// single Alpine instance. Never load Alpine separately.
Livewire.start();
