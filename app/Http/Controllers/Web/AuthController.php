<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function form(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'no_hp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['no_hp' => 'Nomor HP atau password tidak sesuai.'])
                ->onlyInput('no_hp');
        }

        if ($request->user()->role === 'Pelanggan') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['no_hp' => 'Login web hanya tersedia untuk akun Kasir dan Manajer.'])
                ->onlyInput('no_hp');
        }

        $request->session()->regenerate();

        if ($request->user()->wajib_ganti_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function changePasswordForm(): View
    {
        return view('auth.ganti-password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['password_lama'], $request->user()->password)) {
            return back()->withErrors(['password_lama' => 'Password lama tidak sesuai.']);
        }

        $request->user()->password = Hash::make($data['password']);
        $request->user()->wajib_ganti_password = false;
        $request->user()->save();

        return redirect()->route('dashboard')->with('success', 'Password berhasil diperbarui.');
    }
}
