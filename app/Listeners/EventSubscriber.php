<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EventCreated;
use App\Events\EventDeleting;
use App\Events\EventUpdated;
use App\Models\Event;
use App\Services\RecurrentEvent;
use Illuminate\Events\Dispatcher;

class EventSubscriber
{
    public function __construct(
        private RecurrentEvent $recurrentEvent
    ) {
    }

    public function handleEventCreated(EventCreated $event): void
    {
        $eventModel = $event->getEvent();

        if (! $eventModel->event()->exists()) {
            $recurrentEvents = $this->recurrentEvent->getNextEvents($eventModel);
            $eventModel->events()->saveMany($recurrentEvents);
        }
    }

    public function handleEventUpdated(EventUpdated $event): void
    {
        $eventModel = $event->getEvent();
    }

    public function handleEventDeleting(EventDeleting $event): void
    {
        $eventModel = $event->getEvent();
        $events = match (true) {
            $eventModel->events()->exists() => $eventModel->events()
                ->pluck('id'),
            ($eventModel->event !== null) => $eventModel->event->events()
                ->startsAfter((string) $eventModel->starts_at)
                ->pluck('id'),
            default => [],
        };

        Event::whereIn('id', $events)->delete();
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            EventCreated::class => 'handleEventCreated',
            EventUpdated::class => 'handleEventUpdated',
            EventDeleting::class => 'handleEventDeleting',
        ];
    }
}
