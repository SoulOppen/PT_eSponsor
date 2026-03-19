<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Block extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'type',
        'props',
        'order',
        'is_active',
        'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'props' => 'array',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
