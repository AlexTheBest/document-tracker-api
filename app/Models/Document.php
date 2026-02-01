<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * App\Models\Document
 *
 * @mixin Model
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string $path
 * @property-read Carbon $expires_at
 * @property-read Carbon|null $archived_at
 * @property int $owner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 *
 * @method static Builder|Document newModelQuery()
 * @method static Builder|Document newQuery()
 * @method static Builder|Document query()
 * @method static Builder|Document whereCreatedAt($value)
 * @method static Builder|Document whereExpiresAt($value)
 * @method static Builder|Document whereId($value)
 * @method static Builder|Document whereName($value)
 * @method static Builder|Document whereOwnerId($value)
 * @method static Builder|Document whereUpdatedAt($value)
 * @method static Builder|Document whereArchivedAt($value)
 * @method static Builder|Document expiringSoon()
 * @method static Builder|Document expired()
 * @method static Builder|Document notArchived()
 *
 * @mixin Eloquent
 */
class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'path',
        'expires_at',
        'owner_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Scope to get documents expiring within the next 7 days
     */
    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->whereBetween('expires_at', [
            now(),
            now()->addDays(7),
        ])->whereNull('archived_at');
    }

    /**
     * Scope to get expired but not archived documents
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now())
            ->whereNull('archived_at');
    }

    /**
     * Scope to get non-archived documents
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Archive the document
     */
    public function archive(): bool
    {
        $this->archived_at = now();
        return $this->save();
    }

    /**
     * Check if document is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if document is expiring soon (within 7 days)
     */
    public function isExpiringSoon(): bool
    {
        return $this->expires_at->isBetween(now(), now()->addDays(7));
    }

    /**
     * Get the download URL for the document
     */
    public function getDownloadUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
