<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_has_access_to_bookings_feature()
    {
        $user = User::factory()->create(['role_id' => Role::ROLE_USER]);
        $response = $this->actingAs($user)->getJson('api/v1/user/bookings');

        $response->assertStatus(200);
    }

    public function test_property_owner_does_not_have_access_to_bookings_feature()
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $response = $this->actingAs($owner)->getJson('api/v1/user/bookings');

        $response->assertStatus(403);
    }
}
