<?php

namespace App\Http/Controllers/Admin;

use App\Http/Controllers/Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Megjeleníti a Hostadmin beállítások panelt.
     */
    public function index()
    {
        $settings = Setting::all();

        // Itt majd átadjuk a Hostadmin Blade nézetének (ezt később készítjük el)
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Mentia módosított beállításokat a Hostadmin felületről.
     */
    public function update(Request $request)
    {
        // Validáljuk, hogy a beküldött adatok tömb formátumúak legyenek
        $request->validate([
            'settings' => 'required|array',
        ]);

        // Végigmegyünk az összes beküldött kulcson és frissítjük az értékeiket
        foreach ($request->input('settings') as $key => $value) {
            Setting::where('key', $key)->update([
                'value' => $value
            ]);
        }

        return redirect()->back()->with('success', 'A rendszerbeállítások sikeresen frissültek!');
    }
}
