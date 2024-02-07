<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Api;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
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
        $event = Event::factory();
        $event = $isRecurrent ? $event->recurrent()->create() : $event->create();
        $response = $this->get(route('events.show', $parameters));
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(
                [
                    'data' => $eventAttributes,
                ]
            );
        $this->assertSame($event->id, $response['data']['id']);
    }

    public function test_create_event(): void
    {
        $data = [
            'title' => 'Event',
            'description' => 'Information about interesting event',
            'start_at' => Carbon::now()->subHour()->toAtomString(),
            'end_at' => Carbon::now()->addHour()->toAtomString(),
            'recurrent' => false,
        ];
        $response = $this->json(Request::METHOD_POST, route('events.store'), $data);
        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(
                [
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'start_at',
                        'end_at',
                        'recurrent',
                        'created_at',
                        'updated_at',
                    ],
                ]
            );
        $this->assertSame($data['title'], $response['data']['title']);
        $this->assertSame($data['description'], $response['data']['description']);
        $this->assertSame($data['start_at'], $response['data']['start_at']);
        $this->assertSame($data['end_at'], $response['data']['end_at']);
        $this->assertSame($data['recurrent'], $response['data']['recurrent']);
    }

    public static function failedEventProvider(): array
    {
        return [
            [
                [],
                [
                    'title',
                    'start_at',
                    'end_at',
                    'recurrent',
                ],
            ],
            [
                [
                    'title' => 1,
                    'description' => 2,
                    'start_at' => Carbon::now()->toDateTimeImmutable(),
                    'end_at' => Carbon::now()->toDateTimeImmutable(),
                    'recurrent' => false,
                ],
                [
                    'title',
                    'description',
                    'start_at',
                    'end_at',
                ],
            ],
            [
                [
                    'title' => 'title',
                    'start_at' => Carbon::now()->startOfHour()->toAtomString(),
                    'end_at' => Carbon::now()->startOfHour()->toAtomString(),
                    'recurrent' => true,
                ],
                [
                    'end_at',
                    'frequency',
                    'repeat_until',
                ],
            ],
            [
                [
                    'title' => 'title',
                    'start_at' => Carbon::now()->startOfHour()->toAtomString(),
                    'end_at' => Carbon::now()->endOfHour()->toAtomString(),
                    'recurrent' => true,
                    'frequency' => 'bi-weekly',
                    'repeat_until' => Carbon::now()->endOfHour()->toAtomString(),
                ],
                [
                    'frequency',
                    'repeat_until',
                ],
            ],
        ];
    }

    #[DataProvider('failedEventProvider')]
    public function test_failed_create_event(array $data, array $errorList): void
    {
        $response = $this->json(Request::METHOD_POST, route('events.store'), $data);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(
                [
                    'errors' => $errorList,
                ]
            );
    }

    public function test_update_event(): void
    {
        $event = Event::factory()->create();
        $data = [
            'title' => 'Event',
            'description' => 'Information about interesting event',
            'start_at' => Carbon::now()->subHour()->toAtomString(),
            'end_at' => Carbon::now()->addHour()->toAtomString(),
            'recurrent' => false,
        ];
        $response = $this->json(
            Request::METHOD_PUT,
            route('events.update', ['event' => $event->id]),
            $data,
        );
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(
                [
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'start_at',
                        'end_at',
                        'recurrent',
                        'created_at',
                        'updated_at',
                    ],
                ]
            );
        $this->assertSame($data['title'], $response['data']['title']);
        $this->assertSame($data['description'], $response['data']['description']);
        $this->assertSame($data['start_at'], $response['data']['start_at']);
        $this->assertSame($data['end_at'], $response['data']['end_at']);
        $this->assertSame($data['recurrent'], $response['data']['recurrent']);
    }

    #[DataProvider('failedEventProvider')]
    public function test_failed_update_event(array $data, array $errorList): void
    {
        $event = Event::factory()->create();
        $response = $this->json(
            Request::METHOD_PUT,
            route('events.update', ['event' => $event->id]),
            $data
        );
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure(
                [
                    'errors' => $errorList,
                ]
            );
    }

    public function test_update_not_existing_event(): void
    {
        $data = [
            'title' => 'Event',
            'description' => 'Information about interesting event',
            'start_at' => Carbon::now()->subHour()->toAtomString(),
            'end_at' => Carbon::now()->addHour()->toAtomString(),
            'recurrent' => false,
        ];

        $response = $this->json(
            Request::METHOD_PUT,
            route('events.update', ['event' => 1]),
            $data
        );
        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure(
                [
                    'message',
                    'exception',
                ]
            );
    }

    public function test_delete_event(): void
    {
        $event = Event::factory()->create();
        $response = $this->json(
            Request::METHOD_DELETE,
            route('events.destroy', ['event' => $event->id]),
        );
        $response->assertStatus(Response::HTTP_NO_CONTENT);
    }

    public function test_delete_not_existing_event(): void
    {
        $response = $this->json(
            Request::METHOD_DELETE,
            route('events.destroy', ['event' => 1]),
        );
        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure(
                [
                    'message',
                    'exception',
                ]
            );
    }
}
