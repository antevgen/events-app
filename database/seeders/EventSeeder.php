<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        Event::factory(20)->create();
        Event::factory(20)->recurrent()->create();
    }
}
