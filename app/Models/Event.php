<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\EventCreated;
use App\Events\EventDeleting;
use App\Events\EventUpdated;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'ends_at',
        'recurrent',
        'frequency',
        'repeat_until',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'repeat_until' => 'datetime',
        'recurrent' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'created' => EventCreated::class,
        'updated' => EventUpdated::class,
        'deleting' => EventDeleting::class,
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'parent_id', 'id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'parent_id');
    }

    public function scopeStartsAfter(Builder $query, string $date): Builder
    {
        return $query->where('starts_at', '>=', Carbon::parse($date))
            ->where('ends_at', '>=', Carbon::parse($date));
    }

    public function scopeEndsBefore(Builder $query, string $date): Builder
    {
        return $query->where('starts_at', '<=', Carbon::parse($date))
            ->orWhere('ends_at', '<=', Carbon::parse($date));
    }
}
