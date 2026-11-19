<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'       => 'Write technical test',
            'description' => 'Complete the Laravel CRUD API',
            'status'      => 'pending',
            'assignee'    => 'Yoagner',
            'due_date'    => '2026-12-01',
        ], $overrides);
    }

    public function test_can_create_a_task(): void
    {
        $response = $this->postJson('/api/tasks', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('title', 'Write technical test')
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('tasks', [
            'title'    => 'Write technical test',
            'status'   => 'pending',
            'assignee' => 'Yoagner',
        ]);
    }

    public function test_validation_fails_with_invalid_payload(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title'    => '',
            'status'   => 'invalid-status',
            'assignee' => '',
            'due_date' => 'not-a-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'status', 'assignee', 'due_date']);
    }

    public function test_can_list_tasks_filtered_by_status(): void
    {
        Task::factory()->create(['status' => 'pending', 'title' => 'Pending task']);
        Task::factory()->create(['status' => 'done', 'title' => 'Done task']);
        Task::factory()->create(['status' => 'done', 'title' => 'Another done task']);

        $response = $this->getJson('/api/tasks?status=done');

        $response->assertStatus(200)
            ->assertJsonPath('total', 2)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $task) {
            $this->assertSame('done', $task['status']);
        }
    }

    public function test_can_delete_a_task(): void
    {
        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
