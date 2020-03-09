<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vacancy;
use App\Models\Candidate;
use App\Models\HiringProcess;
use App\Models\Interview;
use App\Models\Comment;
use Auth;
use Carbon\Carbon;

class InterviewController extends Controller
{

    public function index()
    {
        $current = Interview::where('date', '>=', Carbon::now())
            ->whereIn('user_id', Auth::user()->account->userIds())
            ->orderBy('date', 'asc')
            ->with(['hiringProcess.vacancy', 'hiringProcess.candidate'])
            ->get();

        $needsFeedback = Interview::where('date', '<', Carbon::now())
            ->whereIn('user_id', Auth::user()->account->userIds())
            ->whereFeedbackGiven(false)
            ->orderBy('date', 'desc')
            ->with(['hiringProcess.vacancy', 'hiringProcess.candidate'])
            ->get();

        $past = Interview::whereBetween('date',[Carbon::now()->subDays(30), Carbon::now()])
            ->whereIn('user_id', Auth::user()->account->userIds())
            // ->whereFeedbackGiven(true)
            ->orderBy('date', 'desc')
            ->with(['hiringProcess.vacancy', 'hiringProcess.candidate'])
            ->limit(100)
            ->get();

        return $this->wasSuccess([
            'current' => $current,
            'needsFeedback' => $needsFeedback,
            'past' => $past
        ]);
    }

    public function show(Request $request, Interview $interview)
    {
        $vacancy = $interview->vacancy();
        $this->authorize('interact-with', $vacancy);
        $this->authorize('view', $vacancy);

        return $this->wasSuccess($interview);
    }

    public function create(Request $request, Vacancy $vacancy, Candidate $candidate)
    {
        $hiringProcess = HiringProcess::firstOrCreate([
            'account_id' => Auth::user()->account_id,
            'vacancy_id' => $vacancy->id,
            'candidate_id' => $candidate->id
        ]);

        $this->authorize('interact-with', $vacancy);
        $this->authorize('interact-with', $candidate);

        $vacancy->prospects()->detach($candidate);

        $interview = Interview::create([
            'user_id' => Auth::user()->id,
            'hiring_process_id' => $hiringProcess->id,
            'type' => $request->type ?? null,
            'location' => $request->location ?? null,
            'date' => Carbon::parse($request->date) ?? null,
            'confirmed' => $request->confirmed ?? false,
            'interviewers' => $request->interviewers ?? null,
            'feedback_status' => 'PENDING'
        ]);

        $this->addComment('Interview booked for', $candidate, $vacancy);

        return $this->wasSuccess($interview);
    }


    public function update(Request $request, Interview $interview)
    {
        $vacancy = $interview->vacancy();
        $this->authorize('interact-with', $vacancy);
        $this->authorize('view', $vacancy);

        $interview->type = $request->type;
        $interview->location = $request->location ?? $interview->location;
        $interview->date = $request->date ? Carbon::parse($request->date) : $interview->date;
        $interview->interviewers = $request->interviewers ?? $interview->interviewers;
        $interview->confirmed = $request->confirmed ?? $interview->confirmed;
        $interview->feedback_given = $request->feedback_given ?? $interview->feedback_given;
        $interview->feedback_status = $request->feedback_status ?? $interview->feedback_status;
        $interview->feedback = $request->feedback ?? $interview->feedback;
        $interview->save();        

        return $this->wasSuccess($interview);
    }


    public function delete(Request $request, Interview $interview)
    {
        $vacancy = $interview->vacancy();
        $candidate = $interview->hiringProcess->candidate;
        $this->authorize('interact-with', $vacancy);
        $this->authorize('interact-with', $candidate);
        $this->authorize('view', $vacancy);

        $interview->delete();       

        $this->addComment('Interview cancelled for', $candidate, $vacancy);

        return $this->wasSuccess($interview);
    }


    public function interviewers()
    {
        $data = [];
        $users = Auth::user()->account->users()->whereIsActive(true)->get();
        $managers = Auth::user()->account->managers()->whereArchieved(false)->get();

        foreach($users as $user){
            array_push($data, $user->name);
        }

        foreach($managers as $manager){
            array_push($data, $manager->name);
        }

        return $this->wasSuccess($data);

    }


    private function addComment($message, $candidate, $vacancy)
    {

        $comment = Comment::create([
            'user_id' => Auth::user()->id,
            'account_id' => Auth::user()->account_id,
            'text' => $message . ' ' . $vacancy->title,
            'type' => 'AUTOMATIC'
        ]);

        $candidate->comments()->save($comment);

        return;

    }
    

}
