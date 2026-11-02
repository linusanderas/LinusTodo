<?php

namespace Tests\Feature;

use App\Enums\ItemKind;
use App\Models\Item;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipeTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_bottom_item_can_be_completed_and_the_next_one_drops_down(): void
    {
        $project = Project::query()->create(['name' => 'Jobb', 'position' => 0]);
        $bottom = $this->item($project, 'Skriv mötesunderlag', ItemKind::Todo, 0);
        $meeting = $this->item($project, 'Möte med teamet', ItemKind::Meeting, 1, '2026-11-03 10:00:00');
        $top = $this->item($project, 'Skicka offert', ItemKind::Todo, 2);

        $this->postJson("/api/projects/{$project->id}/items/{$meeting->id}/complete")
            ->assertStatus(422)
            ->assertJsonPath('errors.item.0', 'Bara det understa i röret kan markeras som klart.');

        $this->postJson("/api/projects/{$project->id}/items/{$top->id}/complete")
            ->assertStatus(422);

        $this->postJson("/api/projects/{$project->id}/items/{$bottom->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.items.0.title', 'Skicka offert')
            ->assertJsonPath('data.items.0.is_bottom', false)
            ->assertJsonPath('data.items.1.title', 'Möte med teamet')
            ->assertJsonPath('data.items.1.kind', 'meeting')
            ->assertJsonPath('data.items.1.is_bottom', true);

        $this->assertNotNull($bottom->fresh()->completed_at);
        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Skriv mötesunderlag']);

        $this->postJson("/api/projects/{$project->id}/items/{$meeting->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.items.0.title', 'Skicka offert')
            ->assertJsonPath('data.items.0.is_bottom', true);
    }

    public function test_a_time_bound_item_can_be_passed_only_when_it_is_at_the_bottom(): void
    {
        $project = Project::query()->create(['name' => 'Hem', 'position' => 0]);
        $bottom = $this->item($project, 'Handla middag', ItemKind::Todo, 0);
        $errand = $this->item($project, 'Lämna in paket', ItemKind::Task, 1, '2026-11-03 16:00:00');

        $this->postJson("/api/projects/{$project->id}/items/{$errand->id}/complete")
            ->assertStatus(422);

        $this->postJson("/api/projects/{$project->id}/items/{$bottom->id}/complete")->assertOk();

        $this->postJson("/api/projects/{$project->id}/items/{$errand->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.items', []);
    }

    public function test_items_can_be_reordered_and_new_ones_land_at_the_top(): void
    {
        $project = Project::query()->create(['name' => 'Jobb', 'position' => 0]);
        $bottom = $this->item($project, 'Först', ItemKind::Todo, 0);
        $middle = $this->item($project, 'Möte', ItemKind::Meeting, 1, '2026-11-03 10:00:00');
        $top = $this->item($project, 'Sist', ItemKind::Todo, 2);

        $this->postJson("/api/projects/{$project->id}/items/{$top->id}/move", ['direction' => 'up'])
            ->assertStatus(422)
            ->assertJsonPath('errors.direction.0', 'Den ligger redan överst.');

        $this->postJson("/api/projects/{$project->id}/items/{$bottom->id}/move", ['direction' => 'down'])
            ->assertStatus(422)
            ->assertJsonPath('errors.direction.0', 'Den ligger redan längst ner.');

        $this->postJson("/api/projects/{$project->id}/items/{$top->id}/move", ['direction' => 'down'])
            ->assertOk()
            ->assertJsonPath('data.items.0.title', 'Möte')
            ->assertJsonPath('data.items.1.title', 'Sist')
            ->assertJsonPath('data.items.2.title', 'Först')
            ->assertJsonPath('data.items.2.is_bottom', true);

        $this->postJson("/api/projects/{$project->id}/items", [
            'title' => 'Ny sak',
            'kind' => 'todo',
        ])->assertCreated()
            ->assertJsonPath('data.items.0.title', 'Ny sak')
            ->assertJsonPath('data.items.0.is_bottom', false)
            ->assertJsonPath('data.items.3.title', 'Först')
            ->assertJsonPath('data.items.3.is_bottom', true);
    }

    public function test_time_bound_items_require_a_datetime_and_todos_do_not(): void
    {
        $project = Project::query()->create(['name' => 'Jobb', 'position' => 0]);

        $this->postJson("/api/projects/{$project->id}/items", [
            'title' => 'Möte',
            'kind' => 'meeting',
        ])->assertStatus(422)
            ->assertJsonPath('errors.occurs_at.0', 'Ange datum och tid.');

        $this->postJson("/api/projects/{$project->id}/items", [
            'title' => 'Vanlig',
            'kind' => 'todo',
            'occurs_at' => '2026-11-03T10:00',
        ])->assertStatus(422)
            ->assertJsonPath('errors.occurs_at.0', 'En vanlig uppgift har ingen tid.');

        $this->postJson("/api/projects/{$project->id}/items", [
            'title' => 'Planering',
            'kind' => 'meeting',
            'occurs_at' => '2026-11-03T10:00',
        ])->assertCreated()
            ->assertJsonPath('data.items.0.kind', 'meeting')
            ->assertJsonPath('data.items.0.is_bottom', true);

        $stored = Item::query()->where('title', 'Planering')->firstOrFail();
        $this->assertSame('2026-11-03 10:00:00', $stored->occurs_at?->format('Y-m-d H:i:s'));
    }

    public function test_projects_are_created_in_order_and_an_item_cannot_be_completed_from_another_pipe(): void
    {
        $first = Project::query()->create(['name' => 'Jobb', 'position' => 0]);
        $item = $this->item($first, 'En sak', ItemKind::Todo, 0);

        $this->postJson('/api/projects', ['name' => 'Hem'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Hem')
            ->assertJsonPath('data.position', 1)
            ->assertJsonPath('data.items', []);

        $this->postJson('/api/projects', ['name' => '   '])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Skriv ett namn på röret.');

        $other = Project::query()->where('name', 'Hem')->firstOrFail();

        $this->postJson("/api/projects/{$other->id}/items/{$item->id}/complete")
            ->assertNotFound();

        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Jobb')
            ->assertJsonPath('data.1.name', 'Hem');
    }

    public function test_the_seeded_pipes_show_what_sits_above_a_deadline(): void
    {
        $this->seed();

        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Jobb')
            ->assertJsonPath('data.0.items.0.title', 'Skicka offert')
            ->assertJsonPath('data.0.items.1.title', 'Följ upp beslut')
            ->assertJsonPath('data.0.items.2.kind', 'meeting')
            ->assertJsonPath('data.0.items.2.title', 'Möte med teamet')
            ->assertJsonPath('data.0.items.3.title', 'Skriv mötesunderlag')
            ->assertJsonPath('data.0.items.3.is_bottom', true)
            ->assertJsonPath('data.1.name', 'Hem')
            ->assertJsonPath('data.1.items.1.kind', 'task')
            ->assertJsonPath('data.1.items.2.is_bottom', true);
    }

    private function item(Project $project, string $title, ItemKind $kind, int $position, ?string $occursAt = null): Item
    {
        return $project->items()->create([
            'title' => $title,
            'kind' => $kind,
            'occurs_at' => $occursAt,
            'position' => $position,
        ]);
    }
}
