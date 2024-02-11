<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(
    name: 'Events',
    description: 'API Endpoints of Events',
)]
class EventController extends Controller
{
    #[OA\Get(
        path: '/events',
        summary: 'List all events',
        tags: ['Events'],
        parameters: [
            new OA\Parameter(
                name: 'filter[starts_after]',
                description: 'Show events that happened after datetime',
                in: 'query',
                schema: new OA\Schema(
                    type: 'string',
                    pattern: '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$',
                    example: '2024-01-10T10:00:00+00:00',
                ),
            ),
            new OA\Parameter(
                name: 'filter[ends_before]',
                description: 'Show events that happened before datetime',
                in: 'query',
                schema: new OA\Schema(
                    type: 'string',
                    pattern: '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$',
                    example: '2024-01-10T17:00:00+00:00',
                ),
            ),
            new OA\Parameter(
                name: 'filter[parent_id]',
                description: 'Show child events of parent one',
                in: 'query',
                schema: new OA\Schema(
                    type: 'integer',
                ),
            ),
            new OA\Parameter(
                name: 'sort',
                description: 'Sort on field(s). Format is `field` to sort in ascending order, `-field` to sort in descending order',
                in: 'query',
                schema: new OA\Schema(
                    type: 'string',
                    enum: [
                        'id',
                        '-id',
                        'starts_at',
                        '-starts_at',
                        'ends_at',
                        '-ends_at',
                    ],
                ),
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'List of events'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized access'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function index(): JsonResponse
    {
        $events = QueryBuilder::for(Event::class)
            ->allowedFilters([
                AllowedFilter::scope('starts_after'),
                AllowedFilter::scope('ends_before'),
                AllowedFilter::exact('parent_id'),
            ])
            ->allowedSorts([
                'starts_at',
                'ends_at',
                'id',
            ])
            ->jsonPaginate()
            ->appends(request()->query());

        return (new EventCollection($events))->response();
    }

    #[OA\Post(
        path: '/events',
        summary: 'Create an Event',
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'title',
                            description: 'Event name',
                            type: 'string',
                            maxLength: 50,
                            nullable: false,
                        ),
                        new OA\Property(
                            property: 'description',
                            description: 'Event description',
                            type: 'string',
                            nullable: true,
                        ),
                        new OA\Property(
                            property: 'starts_at',
                            description: 'Event start time',
                            type: 'string',
                            pattern: '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$',
                            example: '2024-01-10T10:00:00+00:00',
                            nullable: false,
                        ),
                        new OA\Property(
                            property: 'ends_at',
                            description: 'Event end time',
                            type: 'string',
                            pattern: '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$',
                            example: '2024-01-10T17:00:00+00:00',
                            nullable: false,
                        ),
                        new OA\Property(
                            property: 'recurrent',
                            description: 'Is recurrent event',
                            type: 'boolean',
                            default: false,
                            nullable: false,
                        ),
                        new OA\Property(
                            property: 'frequency',
                            description: 'Recurrent frequency',
                            type: 'string',
                            default: null,
                            enum: [
                                'daily',
                                'weekly',
                                'monthly',
                                'yearly',
                            ],
                            nullable: true,
                        ),
                        new OA\Property(
                            property: 'repeat_until',
                            description: 'Repeat recurrent event until',
                            type: 'string',
                            default: null,
                            pattern: '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$',
                            nullable: true,
                        ),
                    ]
                )
            )
        ),
        tags: ['Events'],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Event successfully created'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function store(EventRequest $request): JsonResponse
    {
        $event = Event::create($request->all());

        return (new EventResource($event))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/events/{id}',
        summary: 'Display the specified event',
        tags: ['Events'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Event ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                ),
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Event details'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Event was not found'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized access'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function show(Event $event): JsonResponse
    {
        return (new EventResource($event->load('event')))->response();
    }

    #[OA\Put(
        path: '/events/{id}',
        summary: 'Update the specified Event',
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'title',
                            description: 'Event name',
                            type: 'string',
                            maxLength: 50,
                            nullable: false,
                        ),
                        new OA\Property(
                            property: 'description',
                            description: 'Event description',
                            type: 'string',
                            nullable: true,
                        ),
                        new OA\Property(
                            property: 'starts_at',
                            description: 'Event start time',
                            type: 'string',
                            pattern: '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$',
                            example: '2024-01-10T10:00:00+00:00',
                            nullable: false,
                        ),
                        new OA\Property(
                            property: 'ends_at',
                            description: 'Event end time',
                            type: 'string',
                            pattern: '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$',
                            example: '2024-01-10T17:00:00+00:00',
                            nullable: false,
                        ),
                        new OA\Property(
                            property: 'recurrent',
                            description: 'Is recurrent event',
                            type: 'boolean',
                            default: false,
                            nullable: false,
                        ),
                        new OA\Property(
                            property: 'frequency',
                            description: 'Recurrent frequency',
                            type: 'string',
                            default: null,
                            enum: [
                                'daily',
                                'weekly',
                                'monthly',
                                'yearly',
                            ],
                            nullable: true,
                        ),
                        new OA\Property(
                            property: 'repeat_until',
                            description: 'Repeat recurrent event until',
                            type: 'string',
                            default: null,
                            pattern: '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$',
                            nullable: true,
                        ),
                    ]
                )
            )
        ),
        tags: ['Events'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Event ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                ),
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Event successfully updated'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function update(EventRequest $request, Event $event): JsonResponse
    {
        $event->update($request->all());

        return (new EventResource($event))->response();
    }

    #[OA\Delete(
        path: '/events/{id}',
        summary: 'Delete the specified event',
        tags: ['Events'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Event ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                ),
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Successful operation'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Event was not found'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized access'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function destroy(Event $event): Response
    {
        $event->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
