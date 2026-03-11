<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Calendar;
use Carbon\Carbon;
use Yasumi\Yasumi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    private function getAccessibleCalendarIds(): array
    {
        $user = auth()->user();

        $ownedIds = $user->ownedCalendars()->pluck('id')->toArray();
        $sharedIds = $user->sharedCalendars()->pluck('calendars.id')->toArray();

        return array_values(array_unique(array_merge($ownedIds, $sharedIds)));
    }

    private function getDefaultCalendarId(): ?int
    {
        $user = auth()->user();

        $calendar = $user->ownedCalendars()->first();

        return $calendar?->id;
    }

    public function create(Request $request)
    {
        $selectedDate = $request->input('date');
        $calendarId = $request->input('calendar_id');

        $calendarIds = $this->getAccessibleCalendarIds();

        $calendars = Calendar::whereIn('id', $calendarIds)
            ->orderBy('id')
            ->get();

        return view('events.create', compact('selectedDate', 'calendars', 'calendarId'));
    }

    public function store(Request $request)
    {
        $request->validate($this->validationRules(), $this->validationMessages());

        $startDateTime = Carbon::parse($request->event_date . ' ' . $request->start_time);
        $endDateTime = Carbon::parse($request->end_date . ' ' . $request->end_time);

        if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
            return back()
                ->withErrors(['end_time' => '終了日時は開始日時より後にしてください。'])
                ->withInput();
        }

        $calendarIds = $this->getAccessibleCalendarIds();
        $selectedCalendarId = $request->calendar_id ?? $this->getDefaultCalendarId();

        if (!$selectedCalendarId || !in_array((int) $selectedCalendarId, $calendarIds, true)) {
            abort(403);
        }

        Event::create([
            'user_id' => auth()->id(),
            'calendar_id' => $selectedCalendarId,
            'title' => $request->title,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'color' => $request->color ?? 'green',
            'calendar_type' => $request->calendar_type ?? 'private',
            'notification_time' => $request->notification_time,

            'repeat_type' => $request->repeat_type ?? 'none',
            'repeat_weekdays' => $request->repeat_weekdays ?: null,
            'repeat_month_mode' => $request->repeat_month_mode,
            'repeat_month_nth' => $request->repeat_month_nth,
            'repeat_month_weekday' => $request->repeat_month_weekday,
            'repeat_end_type' => $request->repeat_end_type ?? 'never',
            'repeat_until' => $request->repeat_end_type === 'date' ? $request->repeat_until : null,
            'repeat_count' => $request->repeat_end_type === 'count' ? $request->repeat_count : null,
        ]);

        return redirect('/calendar' . ($selectedCalendarId ? '?calendar_id=' . $selectedCalendarId : ''));
    }

    public function index()
    {
        $calendarIds = $this->getAccessibleCalendarIds();

        $events = Event::whereIn('calendar_id', $calendarIds)
            ->orderBy('event_date', 'asc')
            ->get();

        return view('events.index', compact('events'));
    }

    public function calendar(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $selectedDate = $request->input('selected_date');
        $calendarId = $request->input('calendar_id');

        $date = Carbon::createFromDate($year, $month, 1);

        $currentMonth = $date->month;
        $currentYear = $date->year;

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $daysInMonth = $startOfMonth->daysInMonth;
        $firstDayOfWeek = $startOfMonth->dayOfWeek;

        $prevMonth = $date->copy()->subMonth();
        $nextMonth = $date->copy()->addMonth();

        $calendarStart = $startOfMonth->copy()->subDays($startOfMonth->dayOfWeek);
        $calendarEnd = $endOfMonth->copy()->addDays(6 - $endOfMonth->dayOfWeek);

        $accessibleCalendarIds = $this->getAccessibleCalendarIds();

        $calendars = Calendar::whereIn('id', $accessibleCalendarIds)
            ->orderBy('id')
            ->get();

        $events = $this->buildExpandedEvents($calendarStart, $calendarEnd, $calendarId);

        $holidayData = Cache::remember("jp_holidays_{$currentYear}", 86400, function () use ($currentYear) {
            $holidays = Yasumi::create('Japan', $currentYear, 'ja_JP');
            $dates = [];
            $names = [];

            foreach ($holidays as $holiday) {
                $holidayDate = $holiday->format('Y-m-d');
                $dates[] = $holidayDate;
                $names[$holidayDate] = $holiday->getName();
            }

            return [
                'dates' => $dates,
                'names' => $names,
            ];
        });

        $holidayDates = [];
        $holidayNames = [];

        foreach ($holidayData['dates'] as $holidayDate) {
            $holidayCarbon = Carbon::parse($holidayDate);

            if ($holidayCarbon->between($calendarStart, $calendarEnd)) {
                $holidayDates[] = $holidayDate;
                $holidayNames[$holidayDate] = $holidayData['names'][$holidayDate];
            }
        }

        $today = now()->toDateString();

        $todayExpandedEvents = $this->buildExpandedEvents(
            now()->copy()->startOfDay(),
            now()->copy()->endOfDay(),
            $calendarId
        );

        $todayEvents = $todayExpandedEvents[$today] ?? collect();
        $todayEvents = collect($todayEvents)->take(2);

        return view('events.calendar', compact(
            'currentMonth',
            'currentYear',
            'events',
            'daysInMonth',
            'firstDayOfWeek',
            'prevMonth',
            'nextMonth',
            'holidayDates',
            'holidayNames',
            'selectedDate',
            'calendarId',
            'calendars',
            'today',
            'todayEvents'
        ));
    }

    public function day(Request $request)
    {
        Carbon::setLocale('ja');

        $date = $request->input('date', now()->toDateString());
        $calendarId = $request->input('calendar_id');
        $selectedDate = Carbon::parse($date);

        $expandedEvents = $this->buildExpandedEvents(
            $selectedDate->copy()->startOfDay(),
            $selectedDate->copy()->endOfDay(),
            $calendarId
        );

        $events = $expandedEvents[$selectedDate->format('Y-m-d')] ?? collect();

        return view('events.day', compact('selectedDate', 'events', 'calendarId'));
    }

    public function edit(Request $request, $id)
    {
        $calendarIds = $this->getAccessibleCalendarIds();
        $event = Event::whereIn('calendar_id', $calendarIds)->findOrFail($id);

        $occurrenceDate = $request->input('occurrence_date', $event->event_date);
        $calendarId = $request->input('calendar_id', $event->calendar_id);

        return view('events.edit', compact('event', 'occurrenceDate', 'calendarId'));
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->validationRules(), $this->validationMessages());

        $startDateTime = Carbon::parse($request->event_date . ' ' . $request->start_time);
        $endDateTime = Carbon::parse($request->end_date . ' ' . $request->end_time);

        if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
            return back()
                ->withErrors(['end_time' => '終了日時は開始日時より後にしてください。'])
                ->withInput();
        }

        $calendarIds = $this->getAccessibleCalendarIds();
        $event = Event::whereIn('calendar_id', $calendarIds)->findOrFail($id);

        $selectedCalendarId = $request->calendar_id ?? $event->calendar_id;

        if (!in_array((int) $selectedCalendarId, $calendarIds, true)) {
            abort(403);
        }

        $event->update([
            'calendar_id' => $selectedCalendarId,
            'title' => $request->title,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'color' => $request->color ?? 'green',
            'calendar_type' => $request->calendar_type ?? 'private',
            'notification_time' => $request->notification_time,

            'repeat_type' => $request->repeat_type ?? 'none',
            'repeat_weekdays' => $request->repeat_weekdays ?: null,
            'repeat_month_mode' => $request->repeat_month_mode,
            'repeat_month_nth' => $request->repeat_month_nth,
            'repeat_month_weekday' => $request->repeat_month_weekday,
            'repeat_end_type' => $request->repeat_end_type ?? 'never',
            'repeat_until' => $request->repeat_end_type === 'date' ? $request->repeat_until : null,
            'repeat_count' => $request->repeat_end_type === 'count' ? $request->repeat_count : null,
        ]);

        $redirectDate = $request->event_date;

        return redirect('/calendar/day?date=' . $redirectDate . ($selectedCalendarId ? '&calendar_id=' . $selectedCalendarId : ''));
    }

    public function destroy($id)
    {
        $calendarIds = $this->getAccessibleCalendarIds();
        $event = Event::whereIn('calendar_id', $calendarIds)->findOrFail($id);
        $event->delete();

        return redirect('/calendar');
    }

    private function buildExpandedEvents(Carbon $rangeStart, Carbon $rangeEnd, $calendarId = null)
    {
        $calendarIds = $this->getAccessibleCalendarIds();

        $query = Event::query()
            ->whereIn('calendar_id', $calendarIds)
            ->orderBy('start_time', 'asc');

        if ($calendarId) {
            $query->where('calendar_id', $calendarId);
        }

        $query->whereDate('event_date', '<=', $rangeEnd->toDateString());

        $baseEvents = $query->get();
        $events = [];

        foreach ($baseEvents as $event) {
            $cursor = $rangeStart->copy()->startOfDay();

            while ($cursor->lte($rangeEnd)) {
                if ($this->occursOnDate($event, $cursor)) {
                    $dateKey = $cursor->format('Y-m-d');
                    $events[$dateKey][] = $event;
                }

                $cursor->addDay();
            }
        }

        return collect($events)->map(function ($dayEvents) {
            return collect($dayEvents)->sortBy('start_time')->values();
        });
    }

    public function move(Request $request, $id)
    {
        $request->validate([
            'new_date' => 'required|date',
        ], [
            'new_date.required' => '移動先の日付が必要です。',
            'new_date.date' => '移動先の日付が正しくありません。',
        ]);

        $calendarIds = $this->getAccessibleCalendarIds();
        $event = Event::whereIn('calendar_id', $calendarIds)->findOrFail($id);

        $oldStartDate = Carbon::parse($event->event_date);
        $oldEndDate = Carbon::parse($event->end_date ?? $event->event_date);
        $newStartDate = Carbon::parse($request->new_date);

        $durationDays = $oldStartDate->diffInDays($oldEndDate);
        $newEndDate = $newStartDate->copy()->addDays($durationDays);

        $event->update([
            'event_date' => $newStartDate->format('Y-m-d'),
            'end_date' => $newEndDate->format('Y-m-d'),
        ]);

        return response()->json([
            'success' => true,
            'message' => '予定を移動しました。',
            'event_date' => $event->event_date,
            'end_date' => $event->end_date,
        ]);
    }

    private function occursOnDate(Event $event, Carbon $date): bool
    {
        $eventDate = Carbon::parse($event->event_date)->startOfDay();
        $eventEndDate = Carbon::parse($event->end_date ?? $event->event_date)->startOfDay();

        $repeatType = $event->repeat_type ?? 'none';
        if ($repeatType === null || $repeatType === '') {
            $repeatType = 'none';
        }

        $repeatUntil = $event->repeat_until ? Carbon::parse($event->repeat_until)->startOfDay() : null;
        $repeatCount = $event->repeat_count ? (int) $event->repeat_count : null;

        $repeatWeekdays = is_array($event->repeat_weekdays) ? $event->repeat_weekdays : [];
        $repeatMonthMode = $event->repeat_month_mode ?? 'same_day';
        $repeatMonthNth = $event->repeat_month_nth ?? '1';
        $repeatMonthWeekday = $event->repeat_month_weekday ?? 'mon';

        if ($date->lt($eventDate)) {
            return false;
        }

        if ($repeatUntil && $date->gt($repeatUntil)) {
            return false;
        }

        $matched = false;

        if ($repeatType === 'none') {
            $matched = $date->between($eventDate, $eventEndDate);
        } elseif ($repeatType === 'daily') {
            $matched = true;
        } elseif ($repeatType === 'weekly') {
            $targetWeekdays = [];

            if (!empty($repeatWeekdays)) {
                foreach ($repeatWeekdays as $weekday) {
                    if (array_key_exists($weekday, $this->getWeekdayNumberMap())) {
                        $targetWeekdays[] = $this->getWeekdayNumberMap()[$weekday];
                    }
                }
            } else {
                $targetWeekdays[] = $eventDate->dayOfWeek;
            }

            $matched = in_array($date->dayOfWeek, $targetWeekdays, true);
        } elseif ($repeatType === 'monthly') {
            if ($repeatMonthMode === 'same_day') {
                $matched = $date->day === $eventDate->day;
            } elseif ($repeatMonthMode === 'month_end') {
                $matched = $date->isSameDay($date->copy()->endOfMonth());
            } elseif ($repeatMonthMode === 'weekday_order') {
                $targetWeekday = $this->getWeekdayNumberMap()[$repeatMonthWeekday] ?? null;

                if ($targetWeekday !== null) {
                    $matched = $this->isNthWeekdayOfMonth($date, (string) $repeatMonthNth, $targetWeekday);
                }
            }
        } elseif ($repeatType === 'yearly') {
            $matched = $date->month === $eventDate->month && $date->day === $eventDate->day;
        }

        if (!$matched) {
            return false;
        }

        if ($repeatCount && $repeatType !== 'none') {
            $occurrenceNumber = $this->countOccurrencesUntil($event, $date);
            return $occurrenceNumber <= $repeatCount;
        }

        return true;
    }

    private function countOccurrencesUntil(Event $event, Carbon $targetDate): int
    {
        $eventDate = Carbon::parse($event->event_date)->startOfDay();
        $count = 0;
        $cursor = $eventDate->copy();

        while ($cursor->lte($targetDate)) {
            if ($this->occursOnDateWithoutCount($event, $cursor)) {
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }

    private function occursOnDateWithoutCount(Event $event, Carbon $date): bool
    {
        $eventDate = Carbon::parse($event->event_date)->startOfDay();
        $eventEndDate = Carbon::parse($event->end_date ?? $event->event_date)->startOfDay();

        $repeatType = $event->repeat_type ?? 'none';
        if ($repeatType === null || $repeatType === '') {
            $repeatType = 'none';
        }

        $repeatUntil = $event->repeat_until ? Carbon::parse($event->repeat_until)->startOfDay() : null;

        $repeatWeekdays = is_array($event->repeat_weekdays) ? $event->repeat_weekdays : [];
        $repeatMonthMode = $event->repeat_month_mode ?? 'same_day';
        $repeatMonthNth = $event->repeat_month_nth ?? '1';
        $repeatMonthWeekday = $event->repeat_month_weekday ?? 'mon';

        if ($date->lt($eventDate)) {
            return false;
        }

        if ($repeatUntil && $date->gt($repeatUntil)) {
            return false;
        }

        if ($repeatType === 'none') {
            return $date->between($eventDate, $eventEndDate);
        }

        if ($repeatType === 'daily') {
            return true;
        }

        if ($repeatType === 'weekly') {
            $targetWeekdays = [];

            if (!empty($repeatWeekdays)) {
                foreach ($repeatWeekdays as $weekday) {
                    if (array_key_exists($weekday, $this->getWeekdayNumberMap())) {
                        $targetWeekdays[] = $this->getWeekdayNumberMap()[$weekday];
                    }
                }
            } else {
                $targetWeekdays[] = $eventDate->dayOfWeek;
            }

            return in_array($date->dayOfWeek, $targetWeekdays, true);
        }

        if ($repeatType === 'monthly') {
            if ($repeatMonthMode === 'same_day') {
                return $date->day === $eventDate->day;
            }

            if ($repeatMonthMode === 'month_end') {
                return $date->isSameDay($date->copy()->endOfMonth());
            }

            if ($repeatMonthMode === 'weekday_order') {
                $targetWeekday = $this->getWeekdayNumberMap()[$repeatMonthWeekday] ?? null;

                if ($targetWeekday !== null) {
                    return $this->isNthWeekdayOfMonth($date, (string) $repeatMonthNth, $targetWeekday);
                }
            }

            return false;
        }

        if ($repeatType === 'yearly') {
            return $date->month === $eventDate->month && $date->day === $eventDate->day;
        }

        return false;
    }

    public function checkNotifications(Request $request)
    {
        $now = now();
        $today = $now->toDateString();

        $expandedEvents = $this->buildExpandedEvents(
            $now->copy()->startOfDay(),
            $now->copy()->endOfDay()
        );

        $todayEvents = $expandedEvents[$today] ?? collect();

        $notifications = [];

        foreach ($todayEvents as $event) {
            if (!$event->notification_time || !$event->start_time) {
                continue;
            }

            $eventStart = Carbon::parse($today . ' ' . $event->start_time);
            $notifyAt = $eventStart->copy()->subMinutes((int) $event->notification_time);
            $notifyEnd = $notifyAt->copy()->addMinute();

            if ($now->lt($notifyAt) || $now->gte($notifyEnd)) {
                continue;
            }

            $alreadySent = DB::table('event_notification_logs')
                ->where('event_id', $event->id)
                ->where('occurrence_date', $today)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            DB::table('event_notification_logs')->insert([
                'event_id' => $event->id,
                'occurrence_date' => $today,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $minutes = (int) $event->notification_time;

            if ($minutes === 1440) {
                $timingText = '1日前';
            } elseif ($minutes >= 60) {
                $timingText = ($minutes / 60) . '時間前';
            } else {
                $timingText = $minutes . '分前';
            }

            $notifications[] = [
                'title' => '予定の通知',
                'body' => $event->title . ' が ' . $timingText . 'に始まります。（' . substr($event->start_time, 0, 5) . '）',
                'url' => '/events/' . $event->id . '/edit?occurrence_date=' . $today . '&calendar_id=' . $event->calendar_id,
            ];
        }

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    public function deleteFuture(Request $request, $id)
    {
        $calendarIds = $this->getAccessibleCalendarIds();
        $event = Event::whereIn('calendar_id', $calendarIds)->findOrFail($id);

        $occurrenceDate = $request->input('occurrence_date');

        if (!$occurrenceDate) {
            return back()->with('error', '対象日が取得できませんでした。');
        }

        $occurrence = Carbon::parse($occurrenceDate)->startOfDay();
        $eventDate = Carbon::parse($event->event_date)->startOfDay();

        if ($occurrence->lte($eventDate)) {
            $event->delete();
            return redirect('/calendar');
        }

        if (($event->repeat_type ?? 'none') === 'none') {
            $event->delete();
            return redirect('/calendar');
        }

        $event->update([
            'repeat_until' => $occurrence->copy()->subDay()->format('Y-m-d'),
            'repeat_count' => null,
            'repeat_end_type' => 'date',
        ]);

        return redirect('/calendar');
    }

    public function testNotification()
    {
        return response()->json([
            'notifications' => [
                [
                    'title' => 'テスト通知',
                    'body' => 'Laravelからの通知テストです。',
                    'url' => '/calendar',
                ],
            ],
        ]);
    }

    private function validationRules(): array
    {
        return [
            'title' => 'required|max:255',
            'event_date' => 'required|date',
            'end_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'description' => 'nullable',

            'calendar_id' => 'required|exists:calendars,id',
            'color' => 'nullable|in:green,blue,red,gray,yellow',
            'calendar_type' => 'nullable|in:work,private,family',
            'notification_time' => 'nullable|in:5,10,30,60,1440',

            'repeat_type' => 'nullable|in:none,daily,weekly,monthly,yearly',
            'repeat_weekdays' => 'nullable|array',
            'repeat_weekdays.*' => 'in:mon,tue,wed,thu,fri,sat,sun',

            'repeat_month_mode' => 'nullable|in:same_day,weekday_order,month_end',
            'repeat_month_nth' => 'nullable|in:1,2,3,4,last',
            'repeat_month_weekday' => 'nullable|in:sun,mon,tue,wed,thu,fri,sat',

            'repeat_end_type' => 'nullable|in:never,date,count',
            'repeat_until' => 'nullable|date|after_or_equal:event_date',
            'repeat_count' => 'nullable|integer|min:1',
        ];
    }

    private function validationMessages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'event_date.required' => '開始日を入力してください。',
            'event_date.date' => '開始日は正しい日付で入力してください。',
            'end_date.required' => '終了日を入力してください。',
            'end_date.date' => '終了日は正しい日付で入力してください。',
            'start_time.required' => '開始時間を入力してください。',
            'start_time.date_format' => '開始時間は正しい形式で入力してください。',
            'end_time.required' => '終了時間を入力してください。',
            'end_time.date_format' => '終了時間は正しい形式で入力してください。',
            'calendar_id.required' => 'カレンダーを選択してください。',
            'calendar_id.exists' => '選択したカレンダーが正しくありません。',
            'color.in' => '色の選択が正しくありません。',
            'calendar_type.in' => 'カレンダー種別の選択が正しくありません。',
            'notification_time.in' => '通知の選択が正しくありません。',
            'repeat_type.in' => '繰り返しの選択が正しくありません。',
            'repeat_weekdays.array' => '曜日の選択が正しくありません。',
            'repeat_weekdays.*.in' => '曜日の値が正しくありません。',
            'repeat_month_mode.in' => '月ごとの繰り返し設定が正しくありません。',
            'repeat_month_nth.in' => '第○曜日の選択が正しくありません。',
            'repeat_month_weekday.in' => '曜日の選択が正しくありません。',
            'repeat_end_type.in' => '繰り返し終了条件が正しくありません。',
            'repeat_until.date' => '繰り返し終了日は正しい日付で入力してください。',
            'repeat_until.after_or_equal' => '繰り返し終了日は開始日以降にしてください。',
            'repeat_count.integer' => '繰り返し回数は数字で入力してください。',
            'repeat_count.min' => '繰り返し回数は1回以上にしてください。',
        ];
    }

    private function getWeekdayNumberMap(): array
    {
        return [
            'sun' => 0,
            'mon' => 1,
            'tue' => 2,
            'wed' => 3,
            'thu' => 4,
            'fri' => 5,
            'sat' => 6,
        ];
    }

    private function isNthWeekdayOfMonth(Carbon $date, string $nth, int $targetWeekday): bool
    {
        if ($date->dayOfWeek !== $targetWeekday) {
            return false;
        }

        if ($nth === 'last') {
            return $date->copy()->addWeek()->month !== $date->month;
        }

        $weekIndex = (int) floor(($date->day - 1) / 7) + 1;

        return (string) $weekIndex === (string) $nth;
    }
}