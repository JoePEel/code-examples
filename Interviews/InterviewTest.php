<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Vacancy;
use App\Models\Candidate;
use App\Models\Manager;
use App\Models\Interview;
use App\Models\HiringProcess;
use Carbon\Carbon;

class InterviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_interview_can_be_booked_and_interview_process_is_created()
    {
        $this->withoutExceptionHandling();
        $this->signIn('test');

        $candidate = factory(Candidate::class)->create(['account_id' => $this->user->account_id]);
        $vacancy = factory(Vacancy::class)->create(['user_id' => $this->user->id]);
        $managerOne = factory(Manager::class)->create(['account_id' => $this->user->account_id]);
        $managerTwo = factory(Manager::class)->create(['account_id' => $this->user->account_id]);

        $this->post('/api/interview/' . $vacancy->id . '/' . $candidate->id, [
            'date' => Carbon::now(),
            'location' => 'Board Room',
            'type' => 'Phone',
            'interviewers' => [$this->user->name, $managerOne->name, $managerTwo->name],
        ])->assertStatus(200);

        $this->assertDatabaseHas('hiring_processes', [
            'account_id' => $this->user->account_id,
            'vacancy_id' => $vacancy->id,
            'candidate_id' => $candidate->id,
        ]);

        $this->assertDatabaseHas('interviews', [
            'hiring_process_id' => 1,
            'type' => 'Phone',
            'user_id' => $this->user->id
        ]);

        $this->assertDatabaseHas('comments', [
            'text' => 'Interview booked for ' . $vacancy->title
        ]);

        $interview = Interview::find(1);

        $this->assertEquals(3, count($interview->interviewers));
    }


    public function test_when_an_interview_is_booked_the_candidate_is_removed_from_the_vacancies_prospects()
    {

        $this->withoutExceptionHandling();
        $this->signIn('test');

        $candidate = factory(Candidate::class)->create(['account_id' => $this->user->account_id]);
        $vacancy = factory(Vacancy::class)->create(['user_id' => $this->user->id]);
        $this->post('/api/prospect/' . $vacancy->id . '/' . $candidate->id);

        $this->assertEquals(1, $vacancy->prospects()->get()->count());

        $this->post('/api/interview/' . $vacancy->id . '/' . $candidate->id, [
            'date' => Carbon::now(),
        ])->assertStatus(200);

        $this->assertEquals(0, $vacancy->prospects()->get()->count());
        $this->assertEquals(0, $candidate->prospects()->get()->count());

    }


    public function test_a_list_of_interviews_can_be_returned()
    {
        $this->withoutExceptionHandling();
        $this->signIn('test');

        factory(Interview::class, 3)->create([
            'date' => Carbon::now()->addDays(3), 
            'user_id' => $this->user->id
        ]);
        factory(Interview::class, 3)->create([
            'date' => Carbon::now()->subDays(3), 
            'feedback_given' => true,
            'user_id' => $this->user->id
        ]);
        //Should not be counted
        factory(Interview::class)->create(['user_id' => 443]);

        $res = $this->get('/api/interviews')->decodeResponseJson();

        $this->assertEquals(3, count($res['data']['past']));
        $this->assertEquals(3, count($res['data']['current']));

    }


    public function test_an_interview_can_be_returned()
    {
        $this->withoutExceptionHandling();
        $this->signIn('test');

        $vacancy = factory(Vacancy::class)->create(['user_id' => $this->user->id]);
        $candidate = factory(Candidate::class)->create(['account_id' => $this->user->account_id]);
        $hiringProcess = factory(HiringProcess::class)->create([
            'account_id' => $this->user->account_id,
            'vacancy_id' => $vacancy->id,
            'candidate_id' => $candidate->id,
        ]);
        $interview = factory(Interview::class)->create(['hiring_process_id' => $hiringProcess->id]);

        $this->get('/api/interview/' . $interview->id)
            ->assertJson([
                'data' => [
                    'hiring_process_id' => $interview->hiring_process_id,
                    'user_id' => $interview->user_id,
                    'type' => $interview->type,
                    'location' => $interview->location,
                    'date' => $interview->date
                ]
            ]);
    }


    public function test_an_interview_cannot_be_viewed_of_a_private_vacancy()
    {
        $this->signInNonAdmin('david');

        $differentUser = factory(\App\User::class)->create(['account_id' => $this->user->account_id]);
        $privateVacancy = factory(Vacancy::class)->create(['user_id' => $differentUser->id, 'is_private' => true]);
        $candidate = factory(Candidate::class)->create(['account_id' => $this->user->account_id]);
        $hiringProcess = factory(HiringProcess::class)->create([
            'account_id' => $this->user->account_id,
            'vacancy_id' => $privateVacancy->id,
            'candidate_id' => $candidate->id,
        ]);
        $interview = factory(Interview::class)->create(['hiring_process_id' => $hiringProcess->id]);

        $this->get('/api/interview/' . $interview->id)
            ->assertStatus(403);
    }


    public function test_an_interview_cannot_be_views_from_another_account()
    {
        $this->signIn('david');

        $userFromAnotherAccount = factory(\App\User::class)->create(['account_id' => 432434]);

        $vacancy = factory(Vacancy::class)->create(['user_id' => $userFromAnotherAccount->id]);
        $candidate = factory(Candidate::class)->create(['account_id' => $userFromAnotherAccount->account_id]);
        $hiringProcess = factory(HiringProcess::class)->create([
            'account_id' => $userFromAnotherAccount->account_id,
            'vacancy_id' => $vacancy->id,
            'candidate_id' => $candidate->id,
        ]);
        $interview = factory(Interview::class)->create(['hiring_process_id' => $hiringProcess->id]);

        $this->get('/api/interview/' . $interview->id)
            ->assertStatus(403);
    }


    public function test_an_interview_can_be_updated()
    {
        $this->withoutExceptionHandling();
        $this->signIn('test');

        $vacancy = factory(Vacancy::class)->create(['user_id' => $this->user->id]);
        $candidate = factory(Candidate::class)->create(['account_id' => $this->user->account_id]);
        $hiringProcess = factory(HiringProcess::class)->create([
            'account_id' => $this->user->account_id,
            'vacancy_id' => $vacancy->id,
            'candidate_id' => $candidate->id,
        ]);
        $interview = factory(Interview::class)->create(['hiring_process_id' => $hiringProcess->id]);

        $this->patch('/api/interview/' . $interview->id, [
                'date' => Carbon::now()->addDays(4),
                'location' => 'Meeting Room',
                'type' => 'Face To Face',
        ])->assertStatus(200);

        $this->assertDatabaseHas('interviews', [
            'date' => Carbon::now()->addDays(4),
            'location' => 'Meeting Room',
            'type' => 'Face To Face',
        ]);

        $this->assertEquals(1, Interview::all()->count());
    }


    public function test_an_interview_cannot_be_updated_from_another_account()
    {
        $this->signIn('test');

        $userFromAnotherAccount = factory(\App\User::class)->create(['account_id' => 432434]);

        $vacancy = factory(Vacancy::class)->create(['user_id' => $userFromAnotherAccount->id]);
        $candidate = factory(Candidate::class)->create(['account_id' => $userFromAnotherAccount->account_id]);
        $hiringProcess = factory(HiringProcess::class)->create([
            'account_id' => $userFromAnotherAccount->account_id,
            'vacancy_id' => $vacancy->id,
            'candidate_id' => $candidate->id,
        ]);
        $interview = factory(Interview::class)->create(['hiring_process_id' => $hiringProcess->id]);

        $this->patch('/api/interview/' . $interview->id, [
                'date' => Carbon::now()->addDays(4),
                'location' => 'Meeting Room',
                'type' => 'Face To Face',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('interviews', [
            'date' => Carbon::now()->addDays(4),
            'location' => 'Meeting Room',
            'type' => 'Face To Face',
        ]);

        $this->assertEquals(1, Interview::all()->count());
    }


    public function test_a_interview_can_be_deleted()
    {
        $this->withoutExceptionHandling();
        $this->signIn('test');

        $vacancy = factory(Vacancy::class)->create(['user_id' => $this->user->id]);
        $candidate = factory(Candidate::class)->create(['account_id' => $this->user->account_id]);
        $hiringProcess = factory(HiringProcess::class)->create([
            'account_id' => $this->user->account_id,
            'vacancy_id' => $vacancy->id,
            'candidate_id' => $candidate->id,
        ]);
        $interview = factory(Interview::class)->create(['hiring_process_id' => $hiringProcess->id]);

        $this->delete('/api/interview/' . $interview->id);

        $this->assertDatabaseHas('comments', [
            'text' => 'Interview cancelled for ' . $vacancy->title
        ]);

        $this->assertEquals(0, Interview::all()->count());
    }


    public function test_a_interview_cannot_be_deleted_from_another_account()
    {
        $this->signIn('test');

        $userFromAnotherAccount = factory(\App\User::class)->create(['account_id' => 432434]);

        $vacancy = factory(Vacancy::class)->create(['user_id' => $userFromAnotherAccount->id]);
        $candidate = factory(Candidate::class)->create(['account_id' => $userFromAnotherAccount->account_id]);
        $hiringProcess = factory(HiringProcess::class)->create([
            'account_id' => $userFromAnotherAccount->account_id,
            'vacancy_id' => $vacancy->id,
            'candidate_id' => $candidate->id,
        ]);
        $interview = factory(Interview::class)->create(['hiring_process_id' => $hiringProcess->id]);

        $this->delete('/api/interview/' . $interview->id)
            ->assertStatus(403);

        $this->assertEquals(1, Interview::all()->count());
    }
}
