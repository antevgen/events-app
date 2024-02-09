<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Api;

use App\Enums\RecurrentFrequency;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
                    'starts_at',
                    'ends_at',
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
                    'starts_at',
                    'ends_at',
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
                    'starts_at',
                    'ends_at',
                    'recurrent',
                    'created_at',
                    'updated_at',
                ],
                ['page[size]' => 15],
                15,
            ],
            [
                true,
                [
                    'id',
                    'title',
                    'description',
                    'starts_at',
                    'ends_at',
                    'recurrent',
                    'frequency',
                    'repeat_until',
                    'created_at',
                    'updated_at',
                ],
                ['page[size]' => 15],
                15,
            ],
            [
                true,
                [
                    'id',
                    'title',
                    'description',
                    'starts_at',
                    'ends_at',
                    'recurrent',
                    'frequency',
                    'repeat_until',
                    'created_at',
                    'updated_at',
                ],
                [
                    'page[size]' => 15,
                    'filter[starts_after]' => Carbon::now()->startOfHour()->addDay()->toAtomString(),
                    'filter[ends_before]' => Carbon::now()->startOfHour()->addDays(2)->toAtomString(),
                ],
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
        $events = $isRecurrent
            ? $events->recurrent()
                ->state(new Sequence(
                    fn (Sequence $sequence) => [
                        'starts_at' => Carbon::now()->startOfHour()->addHours($sequence->index + 1)->toAtomString(),
                        'ends_at' => Carbon::now()->endOfHour()->addHours($sequence->index + 1)->toAtomString(),
                        'frequency' => 'daily',
                        'repeat_until' => Carbon::now()->endOfHour()->addDays($sequence->index + 1)->toAtomString(),
                    ],
                ))
                ->create()
            : $events->create(new Sequence(
                fn (Sequence $sequence) => [
                    'starts_at' => Carbon::now()->startOfHour()->addHours($sequence->index + 1)->toAtomString(),
                    'ends_at' => Carbon::now()->endOfHour()->addHours($sequence->index + 1)->toAtomString(),
                ],
            ));
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
        if (count($parameters) > 1) {
            foreach ($response['data'] as $result) {
                $this->assertGreaterThan($parameters['filter[starts_after]'], $result['ends_at']);
                $this->assertLessThan($parameters['filter[ends_before]'], $result['starts_at']);
            }
        }
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
                    'starts_at',
                    'ends_at',
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
                    'starts_at',
                    'ends_at',
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
            'starts_at' => Carbon::now()->subHour()->toAtomString(),
            'ends_at' => Carbon::now()->addHour()->toAtomString(),
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
                        'starts_at',
                        'ends_at',
                        'recurrent',
                        'created_at',
                        'updated_at',
                    ],
                ]
            );
        $this->assertSame($data['title'], $response['data']['title']);
        $this->assertSame($data['description'], $response['data']['description']);
        $this->assertSame($data['starts_at'], $response['data']['starts_at']);
        $this->assertSame($data['ends_at'], $response['data']['ends_at']);
        $this->assertSame($data['recurrent'], $response['data']['recurrent']);
    }

    public function test_create_recurrent_event(): void
    {
        $data = [
            'title' => 'Event',
            'description' => 'Information about interesting event',
            'starts_at' => Carbon::now()->subHour()->toAtomString(),
            'ends_at' => Carbon::now()->addHour()->toAtomString(),
            'recurrent' => true,
            'frequency' => RecurrentFrequency::WEEKLY,
            'repeat_until' => Carbon::now()->addWeek()->toAtomString(),
        ];
        $response = $this->json(Request::METHOD_POST, route('events.store'), $data);
        $recurrentEvents = Event::whereNotNull('parent_id')->get();
        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(
                [
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'starts_at',
                        'ends_at',
                        'recurrent',
                        'created_at',
                        'updated_at',
                    ],
                ]
            );
        $this->assertSame($data['title'], $response['data']['title']);
        $this->assertSame($data['description'], $response['data']['description']);
        $this->assertSame($data['starts_at'], $response['data']['starts_at']);
        $this->assertSame($data['ends_at'], $response['data']['ends_at']);
        $this->assertSame($data['recurrent'], $response['data']['recurrent']);
        $this->assertSame($data['frequency']->value, $response['data']['frequency']);
        $this->assertSame($data['repeat_until'], $response['data']['repeat_until']);
        $this->assertNotEmpty($recurrentEvents);

        foreach ($recurrentEvents as $recurrentEvent) {
            $this->assertDatabaseHas('events', [
                'id' => $recurrentEvent->id,
            ]);
        }
    }

    public static function failedEventProvider(): array
    {
        return [
            [
                [],
                [
                    'title',
                    'starts_at',
                    'ends_at',
                    'recurrent',
                ],
            ],
            [
                [
                    'title' => 1,
                    'description' => 2,
                    'starts_at' => Carbon::now()->toDateTimeImmutable(),
                    'ends_at' => Carbon::now()->toDateTimeImmutable(),
                    'recurrent' => false,
                ],
                [
                    'title',
                    'description',
                    'starts_at',
                    'ends_at',
                ],
            ],
            [
                [
                    'title' => 'title',
                    'starts_at' => Carbon::now()->startOfHour()->toAtomString(),
                    'ends_at' => Carbon::now()->startOfHour()->toAtomString(),
                    'recurrent' => true,
                ],
                [
                    'ends_at',
                    'frequency',
                    'repeat_until',
                ],
            ],
            [
                [
                    'title' => 'title',
                    'starts_at' => Carbon::now()->startOfHour()->toAtomString(),
                    'ends_at' => Carbon::now()->endOfHour()->toAtomString(),
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
            'starts_at' => Carbon::now()->subHour()->toAtomString(),
            'ends_at' => Carbon::now()->addHour()->toAtomString(),
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
                        'starts_at',
                        'ends_at',
                        'recurrent',
                        'created_at',
                        'updated_at',
                    ],
                ]
            );
        $this->assertSame($data['title'], $response['data']['title']);
        $this->assertSame($data['description'], $response['data']['description']);
        $this->assertSame($data['starts_at'], $response['data']['starts_at']);
        $this->assertSame($data['ends_at'], $response['data']['ends_at']);
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
            'starts_at' => Carbon::now()->subHour()->toAtomString(),
            'ends_at' => Carbon::now()->addHour()->toAtomString(),
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
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_delete_recurrent_events(): void
    {
        $event = Event::factory()->recurrent()->create();
        $recurrentEvents = Event::where('parent_id', $event->id)->get();
        $response = $this->json(
            Request::METHOD_DELETE,
            route('events.destroy', ['event' => $event->id]),
        );
        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
        foreach ($recurrentEvents as $recurrentEvent) {
            $this->assertDatabaseMissing('events', [
                'id' => $recurrentEvent->id,
            ]);
        }
    }

    public function test_delete_recurrent_event(): void
    {
        $event = Event::factory()->recurrent()->create();
        $recurrentEvents = Event::where('parent_id', $event->id)->get();
        $response = $this->json(
            Request::METHOD_DELETE,
            route('events.destroy', ['event' => $recurrentEvents->first()->id]),
        );
        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
        ]);
        foreach ($recurrentEvents as $recurrentEvent) {
            $this->assertDatabaseMissing('events', [
                'id' => $recurrentEvent->id,
            ]);
        }
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
