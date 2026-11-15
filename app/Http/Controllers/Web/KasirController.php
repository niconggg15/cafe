<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KasirController extends Controller
{
    public function daftarMenu(): View
    {
        return view('kasir.daftar-menu', [
            'menus' => Menu::orderBy('nama_menu')->paginate(20),
        ]);
    }

    public function resetPasswordForm(): View
    {
        return view('kasir.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['no_hp' => ['required', 'string', 'max:20']]);
        $pelanggan = User::where('no_hp', $data['no_hp'])->where('role', 'Pelanggan')->first();

        if (! $pelanggan) {
            return back()->withErrors(['no_hp' => 'Pelanggan dengan nomor HP tersebut tidak ditemukan.'])->withInput();
        }

        $passwordSementara = Str::random(12);
        $pelanggan->password = Hash::make($passwordSementara);
        $pelanggan->wajib_ganti_password = true;
        $pelanggan->save();

        $route = $request->input('kembali') === 'pesanan' ? 'kasir.pesanan.index' : 'kasir.reset-password';

        return redirect()->route($route)
            ->with('password_sementara', $passwordSementara)
            ->with('reset_pelanggan', $pelanggan->no_hp);
    }
}
