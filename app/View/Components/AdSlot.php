<?php

namespace App\View\Components;

use App\Models\Advertisement;
use App\Services\AdSelector;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdSlot extends Component
{
    public ?Advertisement $advertisement;

    public function __construct(public string $position, AdSelector $selector)
    {
        $this->advertisement = $selector->forPlacement($position);
    }

    public function render(): View|Closure|string
    {
        return view('components.ad-slot');
    }
}
