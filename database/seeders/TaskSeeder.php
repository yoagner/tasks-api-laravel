<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $assignees = ['Armando', 'Ana', 'Luis', 'María', 'Carlos'];

        $titles = [
            'Design database schema',
            'Write API documentation',
            'Implement authentication flow',
            'Review pull request #42',
            'Fix pagination bug',
            'Add unit tests for Task model',
            'Refactor TaskController',
            'Update README with examples',
            'Configure CI pipeline',
            'Optimize slow query on tasks index',
            'Add filtering by assignee',
            'Write migration for tasks table',
            'Set up staging environment',
            'Implement rate limiting',
            'Add validation for due_date',
            'Create Postman collection',
            'Review security headers',
            'Add database indexes',
            'Implement soft deletes',
            'Write integration tests',
            'Document API error codes',
            'Add health check endpoint',
            'Set up logging',
            'Configure CORS',
            'Implement caching layer',
            'Add request throttling',
            'Review N+1 queries',
            'Add OpenAPI spec',
            'Refactor form requests',
            'Prepare demo data',
        ];

        foreach ($titles as $index => $title) {
            Task::create([
                'title'       => $title,
                'description' => $index % 3 === 0 ? null : "Details for: {$title}",
                'status'      => ['pending', 'in_progress', 'done'][$index % 3],
                'assignee'    => $assignees[$index % count($assignees)],
                'due_date'    => now()->addDays($index + 1)->format('Y-m-d'),
            ]);
        }
    }
}
