<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予定一覧</title>
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
</head>
<body>
    <h1>予定一覧</h1>

    <a href="/events/create" class="back-link">予定追加</a>
    <a href="/calendar" class="back-link">カレンダーに戻る</a>

    <table border="1">
        <tr>
            <th>日付</th>
            <th>タイトル</th>
            <th>詳細</th>
        </tr>

        @foreach($events as $event)
        <tr>
            <td>{{ $event->event_date }}</td>
            <td>
                <a href="/events/{{ $event->id }}/edit">{{ $event->title }}</a>
            </td>
            <td>{{ $event->description }}</td>
        </tr>
        @endforeach
    </table>
</body>
</html>