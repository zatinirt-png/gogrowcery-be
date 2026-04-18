<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Bounty extends Model
{
    protected $fillable = [
        'code',
        'client_name',
        'title',
        'description',
        'deadline_at',
        'original_deadline_at',
        'extended_deadline_at',
        'status',
        'created_by',
        'updated_by',
        'published_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at'          => 'datetime',
            'original_deadline_at' => 'datetime',
            'extended_deadline_at' => 'datetime',
            'published_at'         => 'datetime',
            'cancelled_at'         => 'datetime',
        ];
    }

    // Relasi
    public function items()
    {
        return $this->hasMany(BountyItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper status
    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isPublished(): bool  { return $this->status === 'published'; }
    public function isClosed(): bool     { return $this->status === 'closed'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }

    // Deadline aktif (extended jika ada, fallback ke deadline_at)
    public function getActivateDeadlineAttribute()
    {
        return $this->extended_deadline_at ?? $this->deadline_at;
    }

    // Auto-generate code
    public static function generateCode(): string
    {
        $prefix = 'BNT-' . now()->format('Ym') . '-';
        $last   = static::where('code', 'like', $prefix . '%')
                        ->orderByDesc('code')
                        ->value('code');

        $next = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
