<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReferralService;

class PageController extends Controller
{
    public function points(Request $request, ReferralService $referrals)
    {
        return view('pages.points', ['user'=>$request->user(),'inviteUrl'=>route('referrals.accept',$referrals->codeFor($request->user()))]);
    }

    public function acceptReferral(Request $request, string $code, ReferralService $referrals)
    {
        abort_unless($referrals->capture($request,$code),404);

        // A külön landing oldal biztosítja, hogy a Facebook és más megosztási
        // robotok ne egy átirányítást, hanem teljes OG/Twitter metaadatot kapjanak.
        return view('pages.invite', [
            'inviteUrl' => route('referrals.accept', $code),
            'socialImage' => asset('images/invite-fb.png'),
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
