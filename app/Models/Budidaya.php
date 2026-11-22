<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budidaya extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'meja_id',
        'tanggal_semai',
        'jumlah_tanaman',
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
            'user_id' => 'integer',
            'meja_id' => 'integer',
            'tanggal_semai' => 'date',
        ];
    }

    /**
     * Get HSS (Hari Setelah Semai) - days since tanggal_semai
     */
    protected function hss(): Attribute
    {
        return Attribute::make(
            get: fn () => now()->diffInDays($this->tanggal_semai)
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meja(): BelongsTo
    {
        return $this->belongsTo(MejaTanam::class);
    }

    public function panens(): HasMany
    {
        return $this->hasMany(Panen::class);
    }

    public function diagnosis(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }
}
