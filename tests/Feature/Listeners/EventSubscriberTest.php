<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Enums\RecurrentFrequency;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSubscriberTest extends TestCase
{
    use RefreshDatabase;

    public function test_on_event_created(): void
    {
        $event = Event::factory()->create(
            [
                'starts_at' => Carbon::now()->startOfHour(),
                'ends_at' => Carbon::now()->endOfHour(),
                'recurrent' => true,
                'frequency' => RecurrentFrequency::from('daily')->value,
                'repeat_until' => Carbon::now()->endOfHour()->addWeek(),
            ]
        );

        $event->refresh();

        $this->assertNotEmpty($event->events()->get());
        $this->assertCount(7, $event->events()->get());
        $event->events()->each(function (Event $recurrentEvent, $key) use ($event) {
            $this->assertSame(
                Carbon::parse($event->starts_at)->addDays($key + 1)->toAtomString(),
                $recurrentEvent->starts_at->toAtomString(),
            );
            $this->assertSame(
                Carbon::parse($event->ends_at)->addDays($key + 1)->toAtomString(),
                $recurrentEvent->ends_at->toAtomString(),
            );
        });
    }

    public function test_on_event_deleting(): void
    {
        $event = Event::factory()->recurrent()->create();
        $recurrentEvents = $event->events()->get();

        $recurrentEvents->each(function (Event $recurrentEvent) {
            $this->assertDatabaseHas('events', ['id' => $recurrentEvent->id]);
        });

        $event->delete();

        $recurrentEvents->each(function (Event $recurrentEvent) {
            $this->assertDatabaseMissing('events', ['id' => $recurrentEvent->id]);
        });
    }

    public function test_on_event_deleting_recurrent_event(): void
    {
        $event = Event::factory()->recurrent()->create();
        $recurrentEvents = $event->events()->get();

        $recurrentEvents->each(function (Event $recurrentEvent) {
            $this->assertDatabaseHas('events', ['id' => $recurrentEvent->id]);
        });

        $recurrentEvents->get(1)->delete();

        $recurrentEvents->each(function (Event $recurrentEvent, $key) {
            if ($key === 0) {
                $this->assertDatabaseHas('events', ['id' => $recurrentEvent->id]);
            } else {
                $this->assertDatabaseMissing('events', ['id' => $recurrentEvent->id]);
            }
        });
    }

    public function test_on_update_event_to_non_recurrent(): void
    {
        $event = Event::factory()->recurrent()->create();
        $recurrentEvents = $event->events()->get();

        $recurrentEvents->each(function (Event $recurrentEvent) {
            $this->assertDatabaseHas('events', ['id' => $recurrentEvent->id]);
        });

        $event->update(
            [
                'starts_at' => Carbon::now()->startOfHour(),
                'ends_at' => Carbon::now()->endOfHour(),
                'recurrent' => false,
                'frequency' => null,
                'repeat_until' => null,
            ]
        );

        $recurrentEvents->each(function (Event $recurrentEvent) {
            $this->assertDatabaseMissing('events', ['id' => $recurrentEvent->id]);
        });
    }

    public function test_on_update_event_non_recurrent_to_recurrent(): void
    {
        $event = Event::factory()->create();

        $event->update(
            [
                'starts_at' => Carbon::now()->startOfHour(),
                'ends_at' => Carbon::now()->endOfHour(),
                'recurrent' => true,
                'frequency' => RecurrentFrequency::from('daily')->value,
                'repeat_until' => Carbon::now()->endOfHour()->addWeek(),
            ]
        );

        $recurrentEvents = $event->events()->get();

        $recurrentEvents->each(function (Event $recurrentEvent) {
            $this->assertDatabaseHas('events', ['id' => $recurrentEvent->id]);
        });

        $recurrentEvents->each(function (Event $recurrentEvent, $key) use ($event) {
            $this->assertSame(
                Carbon::parse($event->starts_at)->addDays($key + 1)->toAtomString(),
                $recurrentEvent->starts_at->toAtomString(),
            );
            $this->assertSame(
                Carbon::parse($event->ends_at)->addDays($key + 1)->toAtomString(),
                $recurrentEvent->ends_at->toAtomString(),
            );
        });
    }

    public function test_on_update_event_recurrent_to_non_recurrent(): void
    {
        $event = Event::factory()->recurrent()->create();
        $recurrentEvents = $event->events()->get();

        $recurrentEvents->each(function (Event $recurrentEvent) {
            $this->assertDatabaseHas('events', ['id' => $recurrentEvent->id]);
        });

        $recurrentEvents->get(1)->update(
            [
                'starts_at' => Carbon::now()->startOfHour(),
                'ends_at' => Carbon::now()->endOfHour(),
                'recurrent' => false,
                'frequency' => null,
                'repeat_until' => null,
            ]
        );

        $this->assertNull($recurrentEvents->get(1)->parent_id);

        $recurrentEvents->each(function (Event $recurrentEvent, $key) {
            if ($key === 1) {
                $this->assertDatabaseHas('events', ['id' => $recurrentEvent->id]);
            } else {
                $this->assertDatabaseMissing('events', ['id' => $recurrentEvent->id]);
            }
        });
    }

    public function test_on_update_recurrent_event(): void
    {
        $event = Event::factory()->recurrent()->create();
        $recurrentEvents = $event->events()->get();

        $recurrentEvents->each(function (Event $recurrentEvent) {
            $this->assertDatabaseHas('events', ['id' => $recurrentEvent->id]);
        });

        $event->update(
            [
                'starts_at' => Carbon::now()->startOfHour(),
                'ends_at' => Carbon::now()->endOfHour(),
                'recurrent' => false,
                'frequency' => RecurrentFrequency::from('daily')->value,
                'repeat_until' => Carbon::now()->endOfHour()->addWeek(),
            ]
        );

        $recurrentEvents->each(function (Event $recurrentEvent, $key) {
            $this->assertDatabaseMissing('events', ['id' => $recurrentEvent->id]);
        });

        $event->events()->each(function (Event $recurrentEvent, $key) use ($event) {
            $this->assertSame(
                Carbon::parse($event->starts_at)->addDays($key + 1)->toAtomString(),
                $recurrentEvent->starts_at->toAtomString(),
            );
            $this->assertSame(
                Carbon::parse($event->ends_at)->addDays($key + 1)->toAtomString(),
                $recurrentEvent->ends_at->toAtomString(),
            );
        });
    }

    public function test_on_update_internal_recurrent_event(): void
    {
        $event = Event::factory()->recurrent()->create();
        $recurrentEvents = $event->events()->get();

        $newRecurrentEvent = $recurrentEvents->get(1);

        $newRecurrentEvent->update(
            [
                'starts_at' => Carbon::now()->startOfHour(),
                'ends_at' => Carbon::now()->endOfHour(),
                'recurrent' => false,
                'frequency' => RecurrentFrequency::from('daily')->value,
                'repeat_until' => Carbon::now()->endOfHour()->addWeek(),
            ]
        );

        $this->assertSame($event->id, $recurrentEvents->first()->parent_id);
        $this->assertNull($newRecurrentEvent->parent_id);

        $newRecurrentEvent->events()->each(function (Event $recurrentEvent, $key) use ($newRecurrentEvent) {
            $this->assertSame(
                Carbon::parse($newRecurrentEvent->starts_at)->addDays($key + 1)->toAtomString(),
                $recurrentEvent->starts_at->toAtomString(),
            );
            $this->assertSame(
                Carbon::parse($newRecurrentEvent->ends_at)->addDays($key + 1)->toAtomString(),
                $recurrentEvent->ends_at->toAtomString(),
            );
            $this->assertSame($newRecurrentEvent->id, $recurrentEvent->parent_id);
        });
    }
}
