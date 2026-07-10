<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.manager')]
class ManagerProfilePage extends Component
{
    public function render(): View
    {
        return view('livewire.manager-profile-page');
    }
}
