<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Candidate;
use App\Models\Onboarding;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Offer;

class OnboardingController extends Controller
{

    public function create(Request $request, Offer $offer)
    {
        $this->authorize('interact-with', $offer->candidate);

        $request->validate([
            'name' => 'required|min:3|max:50'
        ]);

        $onboarding = Onboarding::create([
            'offer_id' => $offer->id,
            'name' => $request->name
        ]);

        return $this->wasSuccess($offer->id);
    }


    public function single(Request $request, Offer $offer)
    {
        $this->authorize('interact-with', $offer->candidate);

        $data = Offer::where('id', $offer->id)
            ->withCount([
                'onboarding',
                'onboarding as completed_onboarding_count' => function (Builder $query) {
                    $query->where('completed', true);
                }
            ])
            ->with([
                'candidate.documents',
                'onboarding',
                'vacancy'
            ])
            ->orderBy('start_date', 'desc')
            ->first();

        return $this->wasSuccess($data);

    }


    public function candidates()
    {
        $offers =  Auth::user()
            ->account
            ->offers()
            ->where('status', 'ACCEPTED')
            ->withCount([
                'onboarding',
                'onboarding as completed_onboarding_count' => function (Builder $query) {
                    $query->where('completed', true);
                }
            ])
            ->with([
                'candidate',
                'onboarding',
                'vacancy'
            ])
            ->orderBy('start_date', 'desc')
            ->get();

        
        return $this->wasSuccess($offers);
    }


    public function update(Request $request, Onboarding $onboarding)
    {
        $this->authorize('interact-with', $onboarding->offer->candidate);

        $request->validate([
            'name' => 'min:3|max:50',
            'completed' => 'required'
        ]);

        $onboarding->name = $request->name ?? $onboarding->name;
        $onboarding->completed = $request->completed;
        $onboarding->save();

        return $this->wasSuccess($onboarding);
    }


    public function delete(Request $request, Onboarding $onboarding)
    {
        $this->authorize('interact-with', $onboarding->offer->candidate);

        $onboarding->delete();

        return $this->wasSuccess($onboarding);
    }

}
