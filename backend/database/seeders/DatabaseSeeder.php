<?php

namespace Database\Seeders;

use App\Enums\ItemKind;
use App\Models\Project;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Two example pipes. The bottom item is the one you work.
     * Everything under a dated item has to be finished before that time.
     */
    public function run(): void
    {
        $jobb = Project::query()->create(['name' => 'Jobb', 'position' => 0]);
        $hem = Project::query()->create(['name' => 'Hem', 'position' => 1]);

        $meeting = now()->addDay()->setTime(10, 0);
        $errand = now()->addDay()->setTime(16, 0);

        $jobb->items()->createMany([
            ['title' => 'Skriv mötesunderlag', 'kind' => ItemKind::Todo, 'position' => 0],
            ['title' => 'Möte med teamet', 'kind' => ItemKind::Meeting, 'occurs_at' => $meeting, 'position' => 1],
            ['title' => 'Följ upp beslut', 'kind' => ItemKind::Todo, 'position' => 2],
            ['title' => 'Skicka offert', 'kind' => ItemKind::Todo, 'position' => 3],
        ]);

        $hem->items()->createMany([
            ['title' => 'Handla middag', 'kind' => ItemKind::Todo, 'position' => 0],
            ['title' => 'Lämna in paket', 'kind' => ItemKind::Task, 'occurs_at' => $errand, 'position' => 1],
            ['title' => 'Boka tandläkare', 'kind' => ItemKind::Todo, 'position' => 2],
        ]);
    }
}
