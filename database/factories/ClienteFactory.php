<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'tipo_pessoa' => 'PJ',
            'documento' => $this->faker->unique()->numerify('##############'),
            'razao_social' => $this->faker->company(),
            'nome_fantasia' => $this->faker->company(),
            'regime_tributario' => $this->faker->randomElement(['simples', 'presumido', 'real', 'mei', 'isento']),
            'limite_credito' => $this->faker->randomFloat(2, 0, 50000),
            'logradouro' => $this->faker->streetName(),
            'numero' => (string) $this->faker->numberBetween(1, 9999),
            'complemento' => null,
            'bairro' => $this->faker->word(),
            'cidade' => $this->faker->city(),
            'uf' => $this->faker->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR', 'BA', 'SC', 'PE', 'CE', 'GO']),
            'cep' => $this->faker->numerify('########'),
            'telefone' => null,
            'email' => $this->faker->companyEmail(),
            'observacoes' => null,
            'ativo' => true,
            'created_by' => TenantUser::factory(),
            'updated_by' => TenantUser::factory(),
        ];
    }

    public function pf(): static
    {
        return $this->state(fn () => [
            'tipo_pessoa' => 'PF',
            'documento' => $this->faker->unique()->numerify('###########'),
            'razao_social' => $this->faker->name(),
            'nome_fantasia' => null,
            'regime_tributario' => 'isento',
        ]);
    }
}
