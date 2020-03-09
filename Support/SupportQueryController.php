<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\SupportQuery;
use App\Models\Notification;

class SupportQueryController extends Controller
{
    
    public function supportQuery(Request $request)
    {
        $request->validate([
            'message' => 'required'
        ]);

        $user = Auth::user();

        SupportQuery::create([
            'account_id' => $user->account->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => 'SUPPORT',
            'message' => $request->message
        ]);

        Notification::support($user, $request->message);

        return $this->wasSuccess();

    }


    public function salesQuery(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'name' => 'required',
            'message' => 'required'
        ]);

        SupportQuery::create([
            'name' => $request->name,
            'email' => $request->email,
            'type' => 'SALES',
            'message' => $request->message
        ]);

        Notification::sales($request->name . ' ' . $request->email, $request->message);

        return $this->wasSuccess();
    }
}
