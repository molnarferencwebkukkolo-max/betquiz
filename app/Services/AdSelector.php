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
    public function forPlacement(string $key, ?int $categoryId = null, ?int $quizId = null): ?Advertisement
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

        // A kontextus a gyorsítókulcs része, mert egy oldalon ugyanaz a
        // pozíció eltérő kvíz- vagy kategóriacélzással is előfordulhat.
        $requestKey = "kwizzgo.selected-ad.{$key}.category-".($categoryId ?? 'none').'.quiz-'.($quizId ?? 'none');
        if (request()->attributes->has($requestKey)) {
            return request()->attributes->get($requestKey);
        }

        $ads = $placement->advertisements()
            ->currentlyActive()
            ->forContext($categoryId, $quizId)
            ->get();
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
