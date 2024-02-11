<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RecurrentFrequency;
use App\Models\Event;
use App\Services\RecurrentEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Recurr\Recurrence;

class EventRequest extends FormRequest
{
    public function __construct(private RecurrentEvent $recurrentEvent)
    {
        parent::__construct();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'bail|required|string|max:50',
            'description' => 'bail|nullable|string|max:255',
            'starts_at' => 'bail|required|date_format:'.\DateTime::ATOM,
            'ends_at' => 'bail|required|date_format:'.\DateTime::ATOM.'|after:starts_at',
            'recurrent' => 'bail|required|boolean',
            'frequency' => [
                'bail',
                'exclude_unless:recurrent,true',
                'required',
                Rule::enum(RecurrentFrequency::class),
            ],
            'repeat_until' => 'bail|exclude_unless:recurrent,true|required|date_format:'.\DateTime::ATOM.'|after:ends_at',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (count($validator->failed())) {
                return;
            }

            $eventModel = new Event();
            $event = $eventModel->fill($validator->getData());

            $periods = $this->recurrentEvent->getNextOccurrences($event);

            if ($periods->isEmpty()) {
                $recurrence = new Recurrence(
                    Carbon::parse($event->starts_at)->toDateTimeImmutable(),
                    Carbon::parse($event->ends_at)->toDateTimeImmutable(),
                );
                $periods = collect([$recurrence]);
            }

            /** @var Recurrence $recurrence */
            foreach ($periods as $recurrence) {
                if (
                    $this->isTimeOverlaps(
                        $recurrence->getStart()->format(\DateTime::ATOM),
                        $recurrence->getEnd()->format(\DateTime::ATOM),
                    )
                ) {
                    $validator->errors()->add('overlap', 'Event period should not overlap existing events.');

                    return;
                }
            }
        });
    }

    private function isTimeOverlaps(string $startAt, string $endsAt): bool
    {
        $query = Event::startsAfter($startAt)
            ->endsBefore($endsAt);

        /** @var ?Event $requestedEvent */
        $requestedEvent = request()->route()?->parameter('event');
        if ($requestedEvent) {
            $excludedEvents = match (true) {
                $requestedEvent->events()->exists() => $requestedEvent->events()->get()
                    ->add($requestedEvent)->pluck('id'),
                ($requestedEvent->event !== null) => $requestedEvent->event->events()
                    ->startsAfter((string) $requestedEvent->starts_at)
                    ->pluck('id'),
                default => [],
            };

            $query->whereNotIn('id', $excludedEvents);
        }

        return $query
            ->exists();
    }
}
