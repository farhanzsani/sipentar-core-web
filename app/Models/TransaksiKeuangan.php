<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiKeuangan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'panen_id',
        'jenis',
        'tanggal',
        'jumlah',
        'persentase_kas',
        'kas_operasional',
        'laba_bersih',
        'keterangan',
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
            'panen_id' => 'integer',
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
            'persentase_kas' => 'decimal:2',
            'kas_operasional' => 'decimal:2',
            'laba_bersih' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Users::class);
    }

    public function panen(): BelongsTo
    {
        return $this->belongsTo(Panens::class);
    }
}
