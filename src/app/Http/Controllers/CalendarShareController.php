<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\User;
use Illuminate\Http\Request;

class CalendarShareController extends Controller
{
    public function edit(Calendar $calendar)
    {
        abort_unless($calendar->owner_user_id === auth()->id(), 403);

        $sharedUsers = $calendar->users()
            ->where('users.id', '!=', auth()->id())
            ->get();

        return view('calendars.share', compact('calendar', 'sharedUsers'));
    }

    public function store(Request $request, Calendar $calendar)
    {
        abort_unless($calendar->owner_user_id === auth()->id(), 403);

        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '正しいメールアドレスを入力してください。',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'そのメールアドレスのユーザーは見つかりませんでした。',
            ])->withInput();
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'email' => '自分自身は共有済みです。',
            ])->withInput();
        }

        $calendar->users()->syncWithoutDetaching([
            $user->id => ['role' => 'editor'],
        ]);

        return redirect()
            ->route('calendars.share.edit', $calendar->id)
            ->with('success', '共有相手を追加しました。');
    }
}