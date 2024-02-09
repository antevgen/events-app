<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $events = QueryBuilder::for(Event::class)
            ->allowedFilters([
                AllowedFilter::scope('starts_after'),
                AllowedFilter::scope('ends_before'),
            ])
            ->allowedSorts('starts_at')
            ->jsonPaginate()
            ->appends(request()->query());

        return (new EventCollection($events))->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EventRequest $request): JsonResponse
    {
        $event = Event::create($request->all());

        return (new EventResource($event))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): JsonResponse
    {
        return (new EventResource($event->load('events')))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EventRequest $request, Event $event): JsonResponse
    {
        $event->update($request->all());

        return (new EventResource($event))->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event): Response
    {
        $event->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
