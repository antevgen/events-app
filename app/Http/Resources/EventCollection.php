<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EventCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function withResponse($request, $response): void
    {
        $jsonResponse = json_decode(
            $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        unset($jsonResponse['meta']['links']);
        $response->setContent(
            json_encode($jsonResponse, JSON_THROW_ON_ERROR)
        );
    }
}
