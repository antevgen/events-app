<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(private Event $event)
    {
    }

    public function getEvent(): Event
    {
        return $this->event;
    }
}
