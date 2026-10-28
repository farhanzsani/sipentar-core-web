<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnosis extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'budidaya_id',
        'image_path',
        'hasil',
        'confidence',
        'rekomendasi',
        'tanggal_diagnosis',
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
            'budidaya_id' => 'integer',
            'confidence' => 'decimal:2',
            'tanggal_diagnosis' => 'timestamp',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Users::class);
    }

    public function budidaya(): BelongsTo
    {
        return $this->belongsTo(Budidayas::class);
    }
}
