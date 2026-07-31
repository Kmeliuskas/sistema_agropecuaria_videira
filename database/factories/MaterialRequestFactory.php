<?php

namespace Database\Factories;

use App\Models\MaterialRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialRequest>
 */
class MaterialRequestFactory extends Factory
{
    protected $model = MaterialRequest::class;

    public function definition(): array
    {
        return [
            'code' => 'MR-'.fake()->unique()->numerify('####'),
            'requester_id' => User::factory(),
            'status' => 'solicitado',
            'justification' => fake()->sentence(),
            'observation' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'aprovado']);
    }
}
