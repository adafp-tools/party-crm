<?php

namespace App\Livewire\Users;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Placeholder — the user form is embedded in Users\Index as a modal.
 */
class UserForm extends Component
{
    public function render(): View
    {
        return view('livewire.users.user-form');
    }
}
