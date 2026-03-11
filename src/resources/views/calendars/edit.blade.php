<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カレンダー名編集</title>
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
    <style>
        .calendar-edit-page {
            max-width: 720px;
            margin: 40px auto;
            padding: 24px;
        }

        .calendar-edit-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .calendar-edit-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .calendar-edit-subtitle {
            color: #666;
            margin-bottom: 20px;
        }

        .calendar-edit-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 12px;
            box-sizing: border-box;
        }

        .calendar-edit-button {
            border: none;
            background: #1a73e8;
            color: #fff;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 14px;
            cursor: pointer;
        }

        .calendar-edit-error {
            margin-bottom: 12px;
            color: #d93025;
            font-size: 14px;
        }

        .calendar-edit-back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #1a73e8;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="calendar-edit-page">
        <div class="calendar-edit-card">
            <h1 class="calendar-edit-title">カレンダー名を変更</h1>
            <p class="calendar-edit-subtitle">わかりやすい名前に変更できます。</p>

            <form action="{{ route('calendars.update', $calendar->id) }}" method="POST">
                @csrf

                <input
                    type="text"
                    name="name"
                    class="calendar-edit-input"
                    value="{{ old('name', $calendar->name) }}"
                    placeholder="例：みなとのカレンダー"
                >

                @error('name')
                    <div class="calendar-edit-error">{{ $message }}</div>
                @enderror

                <button type="submit" class="calendar-edit-button">保存する</button>
            </form>

            <a href="/calendar" class="calendar-edit-back">← カレンダーに戻る</a>
        </div>
    </div>
</body>
</html>