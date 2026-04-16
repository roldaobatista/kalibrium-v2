<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Contato;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contato>
 */
class ContatoFactory extends Factory
{
    protected $model = Contato::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'cliente_id' => Cliente::factory(),
            'nome' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'whatsapp' => null,
            'papel' => $this->faker->randomElement(['comprador', 'responsavel_tecnico', 'financeiro', 'outro']),
            'principal' => false,
            'ativo' => true,
        ];
    }
}
