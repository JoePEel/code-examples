<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Notification;
use App\Mail\NotificationEmail;
use App\Models\Account;
use App\User;
use Mail;

class SupportQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_support_query_can_be_sent_from_in_app()
    {
        Mail::fake();
        $this->withoutExceptionHandling();

        $this->signIn('test');

        $response = $this->post('/api/support', [
            'message' => 'This is the message'
        ]);

        $this->assertDatabaseHas('support_queries', [
            'account_id' => $this->user->account->id,
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'type' => 'SUPPORT',
            'message' => 'This is the message'
        ]);

        Mail::assertSent(NotificationEmail::class, 1);

    }


    public function test_a_sales_query_can_be_sent_from_outside_the_app()
    {
        Mail::fake();

        $response = $this->post('/api/support/sales', [
            'email' => 'test@test.com',
            'name' => 'test',
            'message' => 'This is the message'
        ]);

        $this->assertDatabaseHas('support_queries', [
            'name' => 'test',
            'email' => 'test@test.com',
            'type' => 'SALES',
            'message' => 'This is the message'
        ]);

        Mail::assertSent(NotificationEmail::class, 1);

    }
}
