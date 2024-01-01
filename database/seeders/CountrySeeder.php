<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Country;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Country::create([
            'name' => 'India',
            'lat' => 20.5937,
            'long' => 78.9629
        ]);
        Country::create([
            'name' => 'China',
            'lat' => 35.8617,
            'long' => 104.1954
        ]);
    }
}
