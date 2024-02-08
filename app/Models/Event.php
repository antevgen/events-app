<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\EventCreated;
use App\Events\EventDeleting;
use App\Events\EventUpdated;
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
        'start_at',
        'end_at',
        'recurrent',
        'frequency',
        'repeat_until',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
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
}
