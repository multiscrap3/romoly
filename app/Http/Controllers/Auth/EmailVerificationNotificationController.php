<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Kirim ulang email verifikasi.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'))
                ->with('info', 'Email Anda sudah terverifikasi.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Tautan verifikasi baru telah dikirim ke email Anda. Silakan cek kotak masuk (dan folder spam).');
    }
}
