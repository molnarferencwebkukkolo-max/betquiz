<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function points()
    {
        return view('pages.coming-soon', [
            'title' => '🎁 Szerezz Pontot!',
            'subtitle' => 'Hamarosan újabb izgalmas feladatokkal és kihívásokkal gyűjthetsz extra zsetonokat!'
        ]);
    }

    public function usersComingSoon()
    {
        return view('pages.coming-soon', [
            'title' => '👥 Felhasználók Kezelése',
            'subtitle' => 'Hostadmin modul előkészítés alatt...'
        ]);
    }
}
