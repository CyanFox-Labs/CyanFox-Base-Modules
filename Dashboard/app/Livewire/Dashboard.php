<?php

namespace Modules\Dashboard\Livewire;

use App\Livewire\CFComponent;
use Livewire\Component;

class Dashboard extends CFComponent
{
    public function render()
    {
        return $this->renderView('dashboard::livewire.dashboard', __('dashboard::dashboard.tab_title'), 'dashboard::components.layouts.app');
    }
}