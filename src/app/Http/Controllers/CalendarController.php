<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function edit(Calendar $calendar)
    {
        abort_unless($calendar->owner_user_id === auth()->id(), 403);

        return view('calendars.edit', compact('calendar'));
    }

    public function update(Request $request, Calendar $calendar)
    {
        abort_unless($calendar->owner_user_id === auth()->id(), 403);

        $request->validate([
            'name' => ['required', 'max:255'],
        ], [
            'name.required' => 'カレンダー名を入力してください。',
            'name.max' => 'カレンダー名は255文字以内で入力してください。',
        ]);

        $calendar->update([
            'name' => $request->name,
        ]);

        return redirect('/calendar')->with('success', 'カレンダー名を変更しました。');
    }
}