<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Geoobject;

class GeoobjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Geoobject::create([
            'city_id' => 1,
            'name' => 'Indian Military Academy',
            'lat' => 30.3382,
            'long' => 77.9922
        ]);

        Geoobject::create([
            'city_id' => 2,
            'name' => 'Baliqiao',
            'lat' => 32.511,
            'long' => 120.833
        ]);
    }
}
