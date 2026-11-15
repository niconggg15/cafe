<?php

namespace App\Services;

use App\Models\Menu;

class HppService
{
    public function untuk(Menu $menu): float
    {
        return (float) $menu->resep->sum(function ($resep) {
            return (float) $resep->takaran * (float) $resep->bahan->harga_per_satuan;
        });
    }
}
