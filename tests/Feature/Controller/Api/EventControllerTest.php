<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Api;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public static function eventListProvider(): array
    {
        return [
            [
                false,
                [
                    'id',
                    'title',
                    'description',
                    'start_at',
                    'end_at',
                    'recurrent',
                    'created_at',
                    'updated_at',
                ],
                [],
                10,
            ],
            [
                true,
                [
                    'id',
                    'title',
                    'description',
                    'start_at',
                    'end_at',
                    'recurrent',
                    'frequency',
                    'repeat_until',
                    'created_at',
                    'updated_at',
                ],
                [],
                10,
            ],
            [
                false,
                [
                    'id',
                    'title',
                    'description',
                    'start_at',
                    'end_at',
                    'recurrent',
                    'created_at',
                    'updated_at',
                ],
                ['per_page' => 15],
                15,
            ],
            [
                true,
                [
                    'id',
                    'title',
                    'description',
                    'start_at',
                    'end_at',
                    'recurrent',
                    'frequency',
                    'repeat_until',
                    'created_at',
                    'updated_at',
                ],
                ['per_page' => 15],
                15,
            ],
        ];
    }

    #[DataProvider('eventListProvider')]
    public function test_get_event_list(
        bool $isRecurrent,
        array $eventAttributes,
        array $parameters,
        int $expectedPerPage,
    ): void {
        $events = Event::factory(10);
        $events = $isRecurrent ? $events->recurrent()->create() : $events->create();
        $response = $this->get(route('events.index', $parameters));
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(
                [
                    'data' => [
                        '*' => $eventAttributes,
                    ],
                    'links' => [
                        'first',
                        'last',
                        'prev',
                        'next',
                    ],
                    'meta' => [
                        'current_page',
                        'from',
                        'last_page',
                        'path',
                        'per_page',
                        'to',
                        'total',
                    ],
                ]
            );
        $this->assertSame($expectedPerPage, $response['meta']['per_page']);
        $this->assertSame($events->count(), $response['meta']['total']);
    }

    public static function eventProvider(): array
    {
        return [
            [
                false,
                [
                    'id',
                    'title',
                    'description',
                    'start_at',
                    'end_at',
                    'recurrent',
                    'created_at',
                    'updated_at',
                ],
                ['event' => 1],
            ],
            [
                true,
                [
                    'id',
                    'title',
                    'description',
                    'start_at',
                    'end_at',
                    'recurrent',
                    'frequency',
                    'repeat_until',
                    'created_at',
                    'updated_at',
                ],
                ['event' => 1],
            ],
        ];
    }

    #[DataProvider('eventProvider')]
    public function test_get_event(
        bool $isRecurrent,
        array $eventAttributes,
        array $parameters,
    ): void {
        $events = Event::factory();
        $events = $isRecurrent ? $events->recurrent()->create() : $events->create();
        $response = $this->get(route('events.show', $parameters));
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(
                [
                    'data' => $eventAttributes,
                ]
            );
    }
}
