<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Tampilkan form "lupa password".
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Kirim tautan reset password ke email.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Kirim tautan reset. Status selalu dianggap "terkirim" demi keamanan
        // (tidak membocorkan apakah email terdaftar atau tidak).
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Jika email tersebut terdaftar, kami telah mengirim tautan reset password. Silakan cek kotak masuk (dan folder spam).');
        }

        // Untuk throttle, tampilkan pesan rate-limit; selain itu tetap pesan netral.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))
                ->with('warning', 'Anda baru saja meminta tautan reset. Mohon tunggu beberapa saat sebelum mencoba lagi.');
        }

        return back()->with('success', 'Jika email tersebut terdaftar, kami telah mengirim tautan reset password. Silakan cek kotak masuk (dan folder spam).');
    }
}
