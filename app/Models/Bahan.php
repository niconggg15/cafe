<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bahan extends Model
{
    use HasFactory;

    protected $table = 'bahan';

    protected $fillable = ['nama_bahan', 'satuan', 'stok', 'batas_minimum', 'harga_per_satuan', 'harga_diperbarui_at'];

    protected $casts = ['harga_diperbarui_at' => 'datetime'];

    public function resep()
    {
        return $this->hasMany(Resep::class);
    }

    public function stokMasuk()
    {
        return $this->hasMany(StokMasuk::class);
    }
}
