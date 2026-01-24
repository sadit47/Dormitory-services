<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_rooms()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Room::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/v1/admin/rooms');

        $res->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_non_admin_cannot_access_rooms()
    {
        $user = User::factory()->create([
            'role' => 'tenant',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/rooms')
            ->assertStatus(403);
    }
}
