<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\User;
use App\Services\NomorHp;
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

    public function resetPasswordForm(Request $request): View
    {
        $noHp = $request->session()->get('kasir.reset_password.no_hp');
        $pelanggan = $noHp
            ? User::where('no_hp', $noHp)->where('role', 'Pelanggan')->first()
            : null;

        return view('kasir.reset-password', ['pelanggan' => $pelanggan]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'no_hp' => ['required', 'string', 'max:20'],
            'aksi' => ['required', 'in:cari,reset'],
        ]);
        $noHp = NomorHp::normal($data['no_hp']);
        if ($noHp === null) {
            $request->session()->forget('kasir.reset_password.no_hp');

            return back()->withErrors(['no_hp' => 'Format nomor HP tidak valid.'])->withInput();
        }

        $pelanggan = User::where('no_hp', $noHp)->where('role', 'Pelanggan')->first();

        if ($data['aksi'] === 'cari') {
            $request->session()->forget('kasir.reset_password.no_hp');

            if (! $pelanggan) {
                return back()->withErrors(['no_hp' => 'Pelanggan dengan nomor HP tersebut tidak ditemukan.'])->withInput();
            }

            $request->session()->put('kasir.reset_password.no_hp', $noHp);

            return redirect()->route('kasir.reset-password')->withInput(['no_hp' => $noHp]);
        }

        if ($request->session()->get('kasir.reset_password.no_hp') !== $noHp) {
            return back()->withErrors(['no_hp' => 'Cari akun pelanggan terlebih dahulu.'])->withInput();
        }

        if (! $pelanggan) {
            $request->session()->forget('kasir.reset_password.no_hp');

            return back()->withErrors(['no_hp' => 'Pelanggan dengan nomor HP tersebut tidak ditemukan.'])->withInput();
        }

        $passwordSementara = Str::random(12);
        $pelanggan->password = Hash::make($passwordSementara);
        $pelanggan->wajib_ganti_password = true;
        $pelanggan->save();
        $request->session()->forget('kasir.reset_password.no_hp');

        return redirect()->route('kasir.reset-password')
            ->with('password_sementara', $passwordSementara)
            ->with('reset_pelanggan', $pelanggan->no_hp);
    }
}
