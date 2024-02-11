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
            foreach ($recurrentEvents as $recurrentEventAttributes) {
                $recurrentEvent = new Event();
                $recurrentEvent->fill($recurrentEventAttributes);
                $eventModel->events()->save($recurrentEvent);
            }
        }
    }

    public function handleEventUpdated(EventUpdated $event): void
    {
        $eventModel = $event->getEvent();
        if ($eventModel->events()->exists() && ! $eventModel->wasChanged('recurrent')) {
            $eventModel->events()->delete();
            $this->handleEventCreated(new EventCreated($eventModel));
        }

        if ($eventModel->wasChanged('recurrent')) {
            match (true) {
                $eventModel->recurrent => $this->handleEventCreated(new EventCreated($eventModel)),
                $eventModel->events()->exists() => $eventModel->events()->delete(),
                ($eventModel->event !== null) => $this->disableRecurrentChildEvent($eventModel),
                default => null,
            };

            return;
        }

        if ($eventModel->event) {
            $childEvents = $eventModel->event->events()
                ->startsAfter((string) $eventModel->getOriginal('starts_at'))
                ->whereNot('id', $eventModel->id)
                ->get();

            $eventModel->parent_id = null;
            $eventModel->saveQuietly();
            $this->handleEventCreated(new EventCreated($eventModel));

            $childEvents->each(function (Event $childEvent) {
                $childEvent->delete();
            });
        }
    }

    private function disableRecurrentChildEvent(Event $event)
    {
        $parentEvent = $event->event;
        $event->parent_id = null;
        $event->saveQuietly();
        $parentEvent->delete();
    }

    public function handleEventDeleting(EventDeleting $event): void
    {
        $eventModel = $event->getEvent();
        match (true) {
            $eventModel->events()->exists() => $eventModel->events()->delete(),
            ($eventModel->event !== null) => $eventModel->event->events()
                ->startsAfter((string) $eventModel->starts_at)
                ->delete(),
            default => null,
        };
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
