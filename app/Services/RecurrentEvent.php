<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Illuminate\Support\Str;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;

class RecurrentEvent
{
    public function getNextOccurrences(Event $event): array
    {
        if (! $event->recurrent) {
            return [];
        }

        $rule = new Rule();
        $rule->setStartDate($event->start_at);
        $rule->setEndDate($event->end_at);
        $rule->setFreq(Str::upper($event->frequency));
        $rule->setUntil($event->repeat_until);

        $transformer = new ArrayTransformer();

        return $transformer->transform($rule)->slice(1);
    }

    public function getNextEvents(Event $event): Collection
    {
        $nextOccurrences = $this->getNextOccurrences($event);

        /** @var ArrayCollection<Event> $events */
        $events = new ArrayCollection();

        foreach ($nextOccurrences as $nextOccurrence) {
            $recurrentEvent = new Event();
            $recurrentEvent->fill([
                'title' => $event->title,
                'description' => $event->description,
                'start_at' => Carbon::parse($nextOccurrence->getStart())->toAtomString(),
                'end_at' => Carbon::parse($nextOccurrence->getEnd())->toAtomString(),
                'recurrent' => $event->recurrent,
                'frequency' => $event->frequency,
                'repeat_until' => $event->repeat_until,
                'parent_id' => $event->id,
            ]);
            $events[] = $recurrentEvent;
        }

        return $events;
    }
}
