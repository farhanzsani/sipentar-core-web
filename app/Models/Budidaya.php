<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(Users::class);
    }

    public function meja(): BelongsTo
    {
        return $this->belongsTo(MejaTanams::class);
    }
}
