<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>日別予定</title>
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
</head>
<body>
    <h1>{{ $selectedDate->month }}月{{ $selectedDate->day }}日 {{ $selectedDate->isoFormat('dddd') }}</h1>

    <div class="calendar-links">
        <a href="/calendar?year={{ $selectedDate->year }}&month={{ $selectedDate->month }}&selected_date={{ $selectedDate->toDateString() }}@if($calendarId)&calendar_id={{ $calendarId }}@endif">
            カレンダーに戻る
        </a>

        <a href="/events/create?date={{ $selectedDate->toDateString() }}@if($calendarId)&calendar_id={{ $calendarId }}@endif">
            ＋ 予定追加
        </a>
    </div>

    @if($events->isEmpty())
        <p style="margin-top:40px;color:#888;font-size:22px;">
            予定がありません
        </p>
    @else
        <div class="event-list">
            @foreach($events as $event)
                <a href="/events/{{ $event->id }}/edit?occurrence_date={{ $selectedDate->toDateString() }}@if($calendarId)&calendar_id={{ $calendarId }}@endif" class="event-link">
                    <div class="event-card">
                        <div class="event-title">
                            @if($event->start_time)
                                {{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}
                            @endif
                            {{ $event->title }}
                        </div>

                        <div class="event-desc">
                            {{ $event->description }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</body>
</html>