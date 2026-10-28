<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'meja_id',
        'pH',
        'tds_ppm',
        'waktu',
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
            'meja_id' => 'integer',
            'pH' => 'decimal:2',
            'tds_ppm' => 'decimal:2',
            'waktu' => 'timestamp',
        ];
    }

    public function meja(): BelongsTo
    {
        return $this->belongsTo(MejaTanams::class);
    }
}
