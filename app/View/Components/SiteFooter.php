<?php
namespace App\View\Components;

use App\Models\Content;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiteFooter extends Component
{
    public function render(): View|Closure|string
    {
        $groups = Content::query()->publiclyVisible()->where('footer_visible', true)
            ->orderBy('footer_group')->orderBy('footer_order')->get()->groupBy('footer_group');
        return view('components.site-footer', compact('groups'));
    }
}
