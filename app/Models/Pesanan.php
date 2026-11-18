<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    use HasFactory;

    protected $table = 'pesanan';

    protected $fillable = ['kasir_id', 'no_meja', 'metode_bayar', 'status', 'subtotal', 'service_charge', 'pajak', 'total_bayar'];

    public function detail()
    {
        return $this->hasMany(DetailPesanan::class);
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }
}
