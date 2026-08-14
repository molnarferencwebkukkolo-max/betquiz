<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\AdPlacement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdvertisementController extends Controller
{
    public function index()
    {
        $this->authorizeHostadmin();

        $advertisements = Advertisement::query()
            ->with(['placements', 'creator'])
            ->latest()
            ->get();
        $placements = AdPlacement::query()->orderBy('id')->get();

        return view('admin.advertisements.index', compact('advertisements', 'placements'));
    }

    public function store(Request $request)
    {
        $this->authorizeHostadmin();
        $validated = $this->validateAdvertisement($request);

        DB::transaction(function () use ($request, $validated) {
            if ($request->hasFile('image')) {
                $validated['image_path'] = $request->file('image')->store('advertisements', 'public');
            }

            if ($validated['type'] === 'image') {
                $validated['adsense_code'] = null;
            } else {
                $validated['target_url'] = null;
                $validated['alt_text'] = null;
            }

            $validated['created_by'] = auth()->id();
            $placementIds = $validated['placements'];
            unset($validated['placements'], $validated['image']);
            $advertisement = Advertisement::create($validated);
            $advertisement->placements()->sync($placementIds);
        });

        return back()->with('success', 'A hirdetés elkészült.');
    }

    public function update(Request $request, Advertisement $advertisement)
    {
        $this->authorizeHostadmin();
        $validated = $this->validateAdvertisement($request, $advertisement);
        $oldImage = $advertisement->image_path;

        DB::transaction(function () use ($request, $validated, $advertisement) {
            if ($request->hasFile('image')) {
                $validated['image_path'] = $request->file('image')->store('advertisements', 'public');
            } elseif ($validated['type'] === 'adsense') {
                $validated['image_path'] = null;
            }

            if ($validated['type'] === 'image') {
                $validated['adsense_code'] = null;
            } else {
                $validated['target_url'] = null;
                $validated['alt_text'] = null;
            }

            $placementIds = $validated['placements'];
            unset($validated['placements'], $validated['image']);
            $advertisement->update($validated);
            $advertisement->placements()->sync($placementIds);
        });

        if (($request->hasFile('image') || $validated['type'] === 'adsense') && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return back()->with('success', 'A hirdetés módosításai elmentve.');
    }

    public function destroy(Advertisement $advertisement)
    {
        $this->authorizeHostadmin();
        $imagePath = $advertisement->image_path;
        $advertisement->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return back()->with('success', 'A hirdetés törölve lett.');
    }

    private function authorizeHostadmin(): void
    {
        abort_unless(auth()->user()?->isHostadmin(), 403);
    }

    /**
     * A beillesztett kód nem általános HTML-mező: csak felismerhető Google
     * AdSense egységet fogadunk el, és kizárjuk a tipikus XSS belépési pontokat.
     */
    private function validateAdvertisement(Request $request, ?Advertisement $advertisement = null): array
    {
        $type = (string) $request->input('type');
        $adsenseRule = function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! str_contains($value, 'adsbygoogle') ||
                ! str_contains($value, 'pagead2.googlesyndication.com') ||
                ! preg_match('/data-ad-client=["\']ca-pub-\d+["\']/i', $value) ||
                preg_match('/<(iframe|object|embed|form|input|button|a)\b/i', $value) ||
                preg_match('/\son\w+\s*=|javascript\s*:/i', $value) ||
                preg_match('/<script\b[^>]*src=["\'](?!https:\/\/pagead2\.googlesyndication\.com\/)[^"\']+/i', $value)) {
                $fail('Csak a Google AdSense felületéről kimásolt, változatlan hirdetési kód fogadható el.');
            }
        };

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['image', 'adsense'])],
            'image' => [
                Rule::requiredIf($type === 'image' && ! $advertisement?->image_path),
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120',
            ],
            'target_url' => [Rule::requiredIf($type === 'image'), 'nullable', 'url:http,https', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'adsense_code' => [Rule::requiredIf($type === 'adsense'), 'nullable', 'string', 'max:10000', $adsenseRule],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'placements' => ['required', 'array', 'min:1'],
            'placements.*' => ['integer', Rule::exists('ad_placements', 'id')->where('is_active', true)],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
