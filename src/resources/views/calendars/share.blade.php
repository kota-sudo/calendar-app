<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カレンダー共有</title>
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
    <style>
        .share-page {
            max-width: 720px;
            margin: 40px auto;
            padding: 24px;
        }

        .share-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .share-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .share-subtitle {
            color: #666;
            margin-bottom: 20px;
        }

        .share-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .share-input {
            flex: 1;
            min-width: 240px;
            padding: 12px 14px;
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            font-size: 14px;
        }

        .share-button {
            border: none;
            background: #1a73e8;
            color: #fff;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 14px;
            cursor: pointer;
        }

        .share-success {
            margin-bottom: 16px;
            padding: 12px 14px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 10px;
        }

        .share-error {
            margin-top: 8px;
            color: #d93025;
            font-size: 14px;
        }

        .share-list {
            margin-top: 20px;
        }

        .share-user {
            padding: 12px 14px;
            border: 1px solid #ececec;
            border-radius: 10px;
            margin-bottom: 10px;
            background: #fafafa;
        }

        .share-actions {
            margin-top: 24px;
        }

        .share-back {
            display: inline-block;
            text-decoration: none;
            color: #1a73e8;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="share-page">
        <div class="share-card">
            <h1 class="share-title">「{{ $calendar->name }}」を共有</h1>
            <p class="share-subtitle">共有したい相手のメールアドレスを入力してください。</p>

            @if(session('success'))
                <div class="share-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('calendars.share.store', $calendar->id) }}" method="POST" class="share-form">
                @csrf
                <input
                    type="email"
                    name="email"
                    class="share-input"
                    placeholder="example@email.com"
                    value="{{ old('email') }}"
                >
                <button type="submit" class="share-button">共有する</button>
            </form>

            @error('email')
                <div class="share-error">{{ $message }}</div>
            @enderror

            <div class="share-list">
                <h2>共有中のメンバー</h2>

                @if($sharedUsers->isEmpty())
                    <p>まだ共有相手はいません。</p>
                @else
                    @foreach($sharedUsers as $user)
                        <div class="share-user">
                            <strong>{{ $user->name }}</strong><br>
                            <span>{{ $user->email }}</span>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="share-actions">
                <a href="/calendar" class="share-back">← カレンダーに戻る</a>
            </div>
        </div>
    </div>
</body>
</html>