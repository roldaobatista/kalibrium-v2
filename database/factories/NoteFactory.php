<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Note;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
final class NoteFactory extends Factory
{
    protected $model = Note::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
            'version' => 1,
            'last_modified_by_device' => null,
        ];
    }
}
