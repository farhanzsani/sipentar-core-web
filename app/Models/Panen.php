<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Panen extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'budidaya_id',
        'tanggal_panen',
        'berat_kg',
        'status',
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
            'budidaya_id' => 'integer',
            'tanggal_panen' => 'date',
            'berat_kg' => 'decimal:2',
        ];
    }

    public function budidaya(): BelongsTo
    {
        return $this->belongsTo(Budidayas::class);
    }
}
