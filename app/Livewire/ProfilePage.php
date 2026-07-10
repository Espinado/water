<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProfilePage extends Component
{
    public function render(): View
    {
        return view('livewire.profile-page');
    }
}
