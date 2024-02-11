<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Services\RecurrentEvent;
use Carbon\Carbon;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecurrentEventTest extends TestCase
{
    private RecurrentEvent $recurrentEvent;

    private MockObject|Event $eventModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recurrentEvent = new RecurrentEvent();
        $this->eventModel = $this->createMock(Event::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->recurrentEvent,
            $this->eventModel,
        );
        parent::tearDown();
    }

    public function test_get_next_occurrences_for_non_recurrent_event(): void
    {
        $this->eventModel
            ->expects($this->once())
            ->method('__get')
            ->with('recurrent')
            ->willReturn(false);

        $result = $this->recurrentEvent->getNextOccurrences($this->eventModel);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }

    public function test_get_next_occurrences_for_recurrent_event(): void
    {
        $startTime = Carbon::now()->startOfHour();
        $endTime = Carbon::now()->endOfHour();
        $this->eventModel
            ->expects($this->exactly(5))
            ->method('__get')
            ->willReturnMap([
                ['recurrent', true],
                ['starts_at', $startTime],
                ['ends_at', $endTime],
                ['frequency', 'daily'],
                ['repeat_until', Carbon::now()->addWeek()],
            ]);

        $results = $this->recurrentEvent->getNextOccurrences($this->eventModel);

        $this->assertInstanceOf(Collection::class, $results);
        foreach ($results as $index => $result) {
            $this->assertSame(
                Carbon::parse($startTime)->addDays($index)->toAtomString(),
                Carbon::parse($result->getStart())->toAtomString(),
            );
            $this->assertSame(
                Carbon::parse($endTime)->addDays($index)->toAtomString(),
                Carbon::parse($result->getEnd())->toAtomString(),
            );
        }
    }

    public function test_get_next_events_for_non_recurrent_event(): void
    {
        $this->eventModel
            ->expects($this->once())
            ->method('__get')
            ->with('recurrent')
            ->willReturn(false);

        $result = $this->recurrentEvent->getNextEvents($this->eventModel);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }

    public function test_get_next_events_for_recurrent_event(): void
    {
        $startTime = Carbon::now()->startOfHour();
        $endTime = Carbon::now()->endOfHour();
        $this->eventModel
            ->expects($this->exactly(11))
            ->method('__get')
            ->willReturnMap([
                ['recurrent', true],
                ['starts_at', $startTime],
                ['ends_at', $endTime],
                ['frequency', 'daily'],
                ['repeat_until', Carbon::now()->addDay()],
                ['title', 'Recurrent event'],
                ['description', null],
                ['recurrent', true],
                ['frequency', 'daily'],
                ['repeat_until', Carbon::now()->addDay()],
                ['id', 1],
            ]);

        $results = $this->recurrentEvent->getNextEvents($this->eventModel);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(1, $results);
    }
}
