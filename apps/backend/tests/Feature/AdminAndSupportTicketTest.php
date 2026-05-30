<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAndSupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users_but_non_admin_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $adminToken = $admin->createToken('admin')->plainTextToken;
        $memberToken = $member->createToken('member')->plainTextToken;

        $this->withToken($adminToken)
            ->getJson('/api/admin/users')
            ->assertOk();

        $this->withToken($memberToken)
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_user_can_create_and_view_own_ticket(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('member')->plainTextToken;

        $createResponse = $this->withToken($token)
            ->postJson('/api/tickets', [
                'subject' => 'Transfer issue',
                'message' => 'Internal transfer is pending.',
                'priority' => 'high',
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('status', 'open');

        $ticketId = $createResponse->json('id');

        $this->withToken($token)
            ->getJson('/api/tickets/'.$ticketId)
            ->assertOk()
            ->assertJsonPath('subject', 'Transfer issue');

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticketId,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
    }
}
