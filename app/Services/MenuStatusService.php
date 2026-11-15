<?php

namespace App\Services;

use App\Models\Menu;

class MenuStatusService
{
    public function segarkan(?array $menuIds = null): void
    {
        $query = Menu::with('resep.bahan');

        if ($menuIds !== null) {
            $query->whereIn('id', $menuIds);
        }

        foreach ($query->get() as $menu) {
            if ($menu->status === 'Nonaktif' || $menu->resep->isEmpty()) {
                continue;
            }

            $tersedia = $menu->resep->every(function ($resep) {
                return (float) $resep->bahan->stok >= (float) $resep->takaran;
            });

            $menu->status = $tersedia ? 'Aktif' : 'Habis';
            $menu->save();
        }
    }
}
