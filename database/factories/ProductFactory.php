<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'        => $this->faker->words(2, true),
            'unit_price'  => $this->faker->randomFloat(2,  50, 2000),
            'sku'         => strtoupper($this->faker->unique()->bothify('SKU-#####')),
            'stock'       => $this->faker->numberBetween(1000, 5000),
            'reserved'    => 0,
        ];
    }
}
