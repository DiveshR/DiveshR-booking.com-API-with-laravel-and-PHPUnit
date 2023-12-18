<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        City::create([
            'country_id' => 1,
            'name' => 'Dehradun',
            'lat' => 30.3165,
            'long' => 78.0322,
        ]);

        City::create([
            'country_id' => 2,
            'name' => 'Beijing',
            'lat' => 39.9042,
            'long' => 116.4074,
        ]);
    }
}
