<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $itemsPerPage = $request->get('per_page', 10);
        $events = Event::paginate($itemsPerPage)->withQueryString();

        return (new EventCollection($events))->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EventRequest $request): JsonResponse
    {
        return new JsonResponse();
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): JsonResponse
    {
        return (new EventResource($event))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EventRequest $request, Event $event): JsonResponse
    {
        return new JsonResponse();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event): JsonResponse
    {
        return new JsonResponse();
    }
}
