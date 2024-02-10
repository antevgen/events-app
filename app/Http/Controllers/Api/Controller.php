<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Recurrent events api',
    title: 'Recurrent events api documentation',
)]
#[OA\Server(
    url: 'http://events-app.local:8080/api',
    description: 'Recurrent events app server',
)]
class Controller
{
}
