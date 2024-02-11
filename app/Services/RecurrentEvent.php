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
    public function getNextOccurrences(Event $event): Collection
    {
        if (! $event->recurrent) {
            return new ArrayCollection();
        }

        $rule = new Rule();
        $rule->setStartDate($event->starts_at);
        $rule->setEndDate($event->ends_at);
        $rule->setFreq(Str::upper($event->frequency));
        $rule->setUntil($event->repeat_until);

        return (new ArrayTransformer())->transform($rule);
    }

    public function getNextEvents(Event $event): Collection
    {
        $nextOccurrences = $this->getNextOccurrences($event)->slice(1);
        $events = new ArrayCollection();

        foreach ($nextOccurrences as $nextOccurrence) {
            $recurrentEvent = [
                'title' => $event->title,
                'description' => $event->description,
                'starts_at' => Carbon::parse($nextOccurrence->getStart())->toAtomString(),
                'ends_at' => Carbon::parse($nextOccurrence->getEnd())->toAtomString(),
                'recurrent' => $event->recurrent,
                'frequency' => $event->frequency,
                'repeat_until' => $event->repeat_until,
                'parent_id' => $event->id,
            ];
            $events->add($recurrentEvent);
        }

        return $events;
    }
}
