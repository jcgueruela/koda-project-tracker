<?php

declare(strict_types=1);

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects guests on every project endpoint', function () {
    $project = Project::factory()->create();

    $this->getJson('/api/projects')->assertUnauthorized();
    $this->getJson("/api/projects/{$project->id}")->assertUnauthorized();
    $this->postJson('/api/projects', Project::factory()->raw())->assertUnauthorized();
    $this->putJson("/api/projects/{$project->id}", Project::factory()->raw())->assertUnauthorized();
    $this->deleteJson("/api/projects/{$project->id}")->assertUnauthorized();
});

describe('as an authenticated user', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
    });

    it('lists projects with pagination metadata', function () {
        Project::factory()->count(15)->create();

        $response = $this->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'client_name', 'description', 'status', 'priority', 'start_date', 'due_date', 'created_at', 'updated_at']],
                'links',
                'meta',
            ]);
    });

    it('returns the enum backing value for status and priority, not the label', function () {
        Project::factory()->create([
            'status' => ProjectStatus::ON_HOLD,
            'priority' => ProjectPriority::HIGH,
        ]);

        $response = $this->getJson('/api/projects');

        $response->assertJsonPath('data.0.status', 'on_hold')
            ->assertJsonPath('data.0.priority', 'high');
    });

    it('paginates to the second page', function () {
        Project::factory()->count(15)->create();

        $response = $this->getJson('/api/projects?page=2');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    });

    it('finds a project by client name', function () {
        Project::factory()->create(['client_name' => 'Acme Corporation']);
        Project::factory()->create(['client_name' => 'Globex Inc']);

        $response = $this->getJson('/api/projects?search=Acme');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.client_name', 'Acme Corporation');
    });

    it('finds a project by project name', function () {
        Project::factory()->create(['name' => 'Patient Appointment System']);
        Project::factory()->create(['name' => 'Marketing Campaign Dashboard']);

        $response = $this->getJson('/api/projects?search=Appointment');

        $response->assertOk()->assertJsonCount(1, 'data');
    });

    it('searches case-insensitively', function () {
        Project::factory()->create(['client_name' => 'Acme Corporation']);

        $response = $this->getJson('/api/projects?search=acme');

        $response->assertOk()->assertJsonCount(1, 'data');
    });

    it('returns no results when nothing matches the search term', function () {
        Project::factory()->create(['client_name' => 'Acme Corporation']);

        $response = $this->getJson('/api/projects?search=NoSuchClient');

        $response->assertOk()->assertJsonCount(0, 'data');
    });

    it('filters by status', function () {
        Project::factory()->create(['status' => ProjectStatus::ON_HOLD]);
        Project::factory()->create(['status' => ProjectStatus::PLANNING]);

        $response = $this->getJson('/api/projects?status=on_hold');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'on_hold');
    });

    it('filters by priority', function () {
        Project::factory()->create(['priority' => ProjectPriority::HIGH]);
        Project::factory()->create(['priority' => ProjectPriority::LOW]);

        $response = $this->getJson('/api/projects?priority=high');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.priority', 'high');
    });

    it('combines search, status, and priority filters', function () {
        Project::factory()->create([
            'client_name' => 'Acme Corporation',
            'status' => ProjectStatus::ON_HOLD,
            'priority' => ProjectPriority::HIGH,
        ]);
        Project::factory()->create([
            'client_name' => 'Acme Corporation',
            'status' => ProjectStatus::ON_HOLD,
            'priority' => ProjectPriority::LOW,
        ]);

        $response = $this->getJson('/api/projects?search=Acme&status=on_hold&priority=high');

        $response->assertOk()->assertJsonCount(1, 'data');
    });

    it('sorts by an allowed field ascending', function () {
        Project::factory()->create(['name' => 'Zeta Project']);
        Project::factory()->create(['name' => 'Alpha Project']);

        $response = $this->getJson('/api/projects?sort=name&direction=asc');

        $response->assertOk()->assertJsonPath('data.0.name', 'Alpha Project');
    });

    it('sorts by an allowed field descending', function () {
        Project::factory()->create(['name' => 'Zeta Project']);
        Project::factory()->create(['name' => 'Alpha Project']);

        $response = $this->getJson('/api/projects?sort=name&direction=desc');

        $response->assertOk()->assertJsonPath('data.0.name', 'Zeta Project');
    });

    it('falls back to created_at when an unsupported sort field is requested', function () {
        $older = Project::factory()->create(['created_at' => now()->subDay()]);
        $newer = Project::factory()->create(['created_at' => now()]);

        $response = $this->getJson('/api/projects?sort=not_a_real_column');

        $response->assertOk()->assertJsonPath('data.0.id', $newer->id);
    });

    it('shows a single project', function () {
        $project = Project::factory()->create();

        $response = $this->getJson("/api/projects/{$project->id}");

        $response->assertOk()->assertJsonPath('data.id', $project->id);
    });

    it('returns 404 for a project that does not exist', function () {
        $response = $this->getJson('/api/projects/999999');

        $response->assertNotFound();
    });

    it('creates a project with valid data', function () {
        $payload = [
            'name' => 'New Client Portal',
            'client_name' => 'Initech',
            'description' => 'A portal for Initech clients.',
            'status' => ProjectStatus::PLANNING->value,
            'priority' => ProjectPriority::MEDIUM->value,
            'start_date' => '2026-01-01',
            'due_date' => '2026-03-01',
        ];

        $response = $this->postJson('/api/projects', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Client Portal')
            ->assertJsonPath('data.status', 'planning')
            ->assertJsonPath('data.priority', 'medium');

        $this->assertDatabaseHas('projects', [
            'name' => 'New Client Portal',
            'client_name' => 'Initech',
            'status' => 'planning',
            'priority' => 'medium',
        ]);
    });

    it('requires the core fields when creating a project', function () {
        $response = $this->postJson('/api/projects', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'client_name', 'status', 'priority', 'start_date', 'due_date']);
    });

    it('rejects a status value that is not a valid enum backing value', function () {
        $payload = Project::factory()->raw(['status' => 'On Hold']);

        $response = $this->postJson('/api/projects', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    });

    it('rejects a priority value that is not a valid enum backing value', function () {
        $payload = Project::factory()->raw(['priority' => 'High']);

        $response = $this->postJson('/api/projects', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['priority']);
    });

    it('rejects a due date before the start date', function () {
        $payload = Project::factory()->raw([
            'start_date' => '2026-06-01',
            'due_date' => '2026-05-01',
        ]);

        $response = $this->postJson('/api/projects', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date'])
            ->assertJsonFragment(['due_date' => ['Due date cannot be earlier than the start date.']]);
    });

    it('allows the due date to equal the start date', function () {
        $payload = Project::factory()->raw([
            'start_date' => '2026-06-01',
            'due_date' => '2026-06-01',
        ]);

        $response = $this->postJson('/api/projects', $payload);

        $response->assertCreated();
    });

    it('updates a project with valid data', function () {
        $project = Project::factory()->create([
            'name' => 'Old Name',
            'start_date' => '2026-01-01',
            'due_date' => '2026-02-01',
        ]);

        $payload = [
            'name' => 'Updated Name',
            'client_name' => $project->client_name,
            'description' => $project->description,
            'status' => ProjectStatus::COMPLETED->value,
            'priority' => ProjectPriority::LOW->value,
            'start_date' => '2026-01-01',
            'due_date' => '2026-02-01',
        ];

        $response = $this->putJson("/api/projects/{$project->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'status' => 'completed',
        ]);
    });

    it('validates fields on update the same way as create', function () {
        $project = Project::factory()->create();

        $response = $this->putJson("/api/projects/{$project->id}", ['name' => '']);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    });

    it('returns 404 when updating a project that does not exist', function () {
        $payload = Project::factory()->raw();

        $response = $this->putJson('/api/projects/999999', $payload);

        $response->assertNotFound();
    });

    it('deletes a project', function () {
        $project = Project::factory()->create();

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    });

    it('returns 404 when deleting a project that does not exist', function () {
        $response = $this->deleteJson('/api/projects/999999');

        $response->assertNotFound();
    });
});
