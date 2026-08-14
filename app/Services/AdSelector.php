<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\AdPlacement;

class AdSelector
{
    /**
     * Súlyozott véletlen választás. Ugyanazon oldalbetöltés alatt az azonos
     * pozíció ugyanazt a kreatívot kapja, így nem generálunk mesterséges frissítést.
     */
    public function forPlacement(string $key): ?Advertisement
    {
        if (auth()->user()?->isAdFree()) {
            return null;
        }

        $placement = AdPlacement::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        if (! $placement) {
            return null;
        }

        $requestKey = "kwizzgo.selected-ad.{$key}";
        if (request()->attributes->has($requestKey)) {
            return request()->attributes->get($requestKey);
        }

        $ads = $placement->advertisements()->currentlyActive()->get();
        if ($ads->isEmpty()) {
            request()->attributes->set($requestKey, null);
            return null;
        }

        $totalWeight = $ads->sum(fn (Advertisement $ad) => max(1, $ad->weight));
        $ticket = random_int(1, $totalWeight);
        $selected = $ads->first(function (Advertisement $ad) use (&$ticket) {
            $ticket -= max(1, $ad->weight);
            return $ticket <= 0;
        });

        request()->attributes->set($requestKey, $selected);

        return $selected;
    }
}
