<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stok extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama_bahan',
        'kategori',
        'satuan',
        'jumlah_stok',
        'batas_minimum',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'jumlah_stok' => 'decimal:2',
            'batas_minimum' => 'decimal:2',
        ];
    }

    /**
     * Get the attributes that should be appended.
     *
     * @return array<string>
     */
    protected $appends = ['is_low'];

    /**
     * Determine if stock is low
     */
    protected function isLow(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->jumlah_stok <= $this->batas_minimum,
        );
    }

    public function penggunaanOperasionals(): HasMany
    {
        return $this->hasMany(PenggunaanOperasional::class);
    }
}
