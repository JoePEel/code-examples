<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\Models\Onboarding;
use App\Models\Candidate;
use App\Models\Offer;

class OnboardingTest extends TestCase
{

    use RefreshDatabase;

    public function test_an_onboarding_item_can_be_created()
    {
        $this->signIn('test');
        $this->withoutExceptionHandling();

        $candidate = factory(Candidate::class)->create(['account_id' => $this->user->account_id]);
        $offer = factory(Offer::class)->create([
            'candidate_id' => $candidate->id,
            'account_id' => $this->user->account_id
        ]);

        $this->post('/api/onboarding/' . $offer->id, [
            'name' => 'Send Contract',
        ]);

        $this->assertDatabaseHas('onboardings', [
            'name' => 'Send Contract'
        ]);
    }


    public function test_an_onboarding_item_cannot_be_created_for_a_canididate_from_another_account()
    {
        $this->signIn('test');

        $offer = factory(Offer::class)->create(['account_id' => 3234]);

        $this->post('/api/onboarding/' . $offer->id, [
            'name' => 'Send Contract',
        ])
        ->assertStatus(403);

        $this->assertDatabaseMissing('onboardings', [
            'name' => 'Send Contract'
        ]);
    }


    public function test_an_onboarding_can_be_updated()
    {
        $this->withoutExceptionHandling();
        $this->signIn('test');

        $candidate = factory(Candidate::class)->create(['account_id' => $this->user->account_id]);
        $offer = factory(Offer::class)->create([
            'candidate_id' => $candidate->id,
            'account_id' => $this->user->account_id
        ]);
        $onboarding = factory(Onboarding::class)->create(['name' => 'one', 'offer_id' => $offer->id]);

        $this->patch('/api/onboarding/' . $onboarding->id, [
            'name' => 'updated',
            'completed' => true
        ])
        ->assertStatus(200);

        $this->assertDatabaseHas('onboardings', [
            'name' => 'updated',
            'completed' => true
        ]);

        $this->assertEquals(1, Onboarding::all()->count());

    }


    public function test_an_onboarding_cannot_be_updated_from_another_account()
    {
        $this->signIn('test');

        $candidate = factory(Candidate::class)->create(['account_id' => 33333]);
        $offer = factory(Offer::class)->create([
            'candidate_id' => $candidate->id,
            'account_id' => $this->user->account_id
        ]);
        $onboarding = factory(Onboarding::class)->create(['name' => 'one', 'offer_id' => $offer->id]);

        $this->patch('/api/onboarding/' . $onboarding->id, [
            'name' => 'updated',
            'completed' => true
        ])
        ->assertStatus(403);

        $this->assertDatabaseHas('onboardings', [
            'name' => 'one',
            'completed' => false
        ]);

        $this->assertEquals(1, Onboarding::all()->count());

    }


    public function test_an_onboarding_can_be_deleted()
    {
        $this->withoutExceptionHandling();
        $this->signIn('test');

        $candidate = factory(Candidate::class)->create(['account_id' => $this->user->account_id]);
        $offer = factory(Offer::class)->create([
            'candidate_id' => $candidate->id,
            'account_id' => $this->user->account_id
        ]);
        $onboarding = factory(Onboarding::class)->create(['name' => 'one', 'offer_id' => $offer->id]);

        $this->delete('/api/onboarding/' . $onboarding->id)
            ->assertStatus(200);

        $this->assertEquals(0, Onboarding::all()->count());

    }


    public function test_an_onboarding_cannot_be_deleted_from_another_account()
    {
        $this->signIn('test');

        $candidate = factory(Candidate::class)->create(['account_id' => 42323]);
        $offer = factory(Offer::class)->create([
            'candidate_id' => $candidate->id
        ]);
        $onboarding = factory(Onboarding::class)->create(['name' => 'one', 'offer_id' => $offer->id]);

        $this->delete('/api/onboarding/' . $onboarding->id)
            ->assertStatus(403);

        $this->assertEquals(1, Onboarding::all()->count());

    }

}
