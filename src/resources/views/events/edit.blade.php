<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予定編集</title>
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
</head>
<body>
    @php
        $colorMap = [
            'green' => 'エメラルド・グリーン',
            'blue' => 'ディープ・スカイブルー',
            'red' => 'アップル・レッド',
            'gray' => 'グレー',
            'yellow' => 'ブライト・イエロー',
        ];

        $selectedColor = old('color', $event->color ?? 'green');
        $selectedColorLabel = $colorMap[$selectedColor] ?? 'エメラルド・グリーン';

        $selectedCalendarType = old('calendar_type', $event->calendar_type ?? 'private');
        $selectedCalendarId = old('calendar_id', $calendarId ?? $event->calendar_id);

        $repeatType = old('repeat_type', $event->repeat_type ?? 'none');
        $repeatEndType = old('repeat_end_type', $event->repeat_end_type ?? 'never');
        $repeatMonthMode = old('repeat_month_mode', $event->repeat_month_mode ?? 'same_day');
        $repeatMonthNth = old('repeat_month_nth', $event->repeat_month_nth ?? '1');
        $repeatMonthWeekday = old('repeat_month_weekday', $event->repeat_month_weekday ?? 'mon');

        $repeatWeekdays = old('repeat_weekdays', $event->repeat_weekdays ?? []);
        if (!is_array($repeatWeekdays)) {
            $repeatWeekdays = [];
        }

        $backDate = $occurrenceDate ?? $event->event_date;
        $backCalendarId = $calendarId ?? $event->calendar_id;
        $isRepeating = (($event->repeat_type ?? 'none') !== 'none');
    @endphp

    <div class="form-page">
        <div class="form-header">
            <a
                href="/calendar/day?date={{ $backDate }}@if($backCalendarId)&calendar_id={{ $backCalendarId }}@endif"
                class="header-back"
            >
                カレンダーに戻る
            </a>

            <button type="submit" form="eventEditForm" class="header-save">保存</button>
        </div>

        <form method="POST" action="/events/{{ $event->id }}/update" class="event-form" id="eventEditForm">
            @csrf

            <input type="hidden" name="calendar_id" value="{{ $selectedCalendarId }}">

            <div class="form-group">
                <label for="title">タイトル</label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    value="{{ old('title', $event->title) }}"
                    class="@error('title') input-error @enderror"
                >
                @error('title')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="calendar_type">カレンダー種別</label>
                <select id="calendar_type" name="calendar_type" class="@error('calendar_type') input-error @enderror">
                    <option value="work" {{ $selectedCalendarType === 'work' ? 'selected' : '' }}>仕事</option>
                    <option value="private" {{ $selectedCalendarType === 'private' ? 'selected' : '' }}>プライベート</option>
                    <option value="family" {{ $selectedCalendarType === 'family' ? 'selected' : '' }}>家族</option>
                </select>
                @error('calendar_type')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="schedule-row">
                <div class="schedule-label">開始</div>
                <div class="schedule-inputs">
                    <div style="flex: 1;">
                        <div class="schedule-date-wrap @error('event_date') input-error @enderror" id="startDateWrap">
                            <input
                                type="date"
                                name="event_date"
                                class="schedule-date-native"
                                value="{{ old('event_date', \Carbon\Carbon::parse($event->event_date)->format('Y-m-d')) }}"
                                id="startDateInput"
                            >
                            <div class="schedule-date-display" id="startDateDisplay">
                                {{ old('event_date', \Carbon\Carbon::parse($event->event_date)->format('Y-m-d')) ?: '年 / 月 / 日' }}
                            </div>
                            <span class="schedule-icon">📅</span>
                        </div>
                        @error('event_date')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="width: 120px;">
                        <div class="schedule-time-wrap @error('start_time') input-error @enderror" id="startTimeWrap">
                            <input
                                type="time"
                                name="start_time"
                                class="schedule-time-native"
                                value="{{ old('start_time', $event->start_time) }}"
                                id="startTimeInput"
                            >
                            <div class="schedule-time-display" id="startTimeDisplay">
                                {{ old('start_time', $event->start_time) ?: '--:--' }}
                            </div>
                            <span class="schedule-icon">🕒</span>
                        </div>
                        @error('start_time')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="schedule-row">
                <div class="schedule-label">終了</div>
                <div class="schedule-inputs">
                    <div style="flex: 1;">
                        <div class="schedule-date-wrap @error('end_date') input-error @enderror" id="endDateWrap">
                            <input
                                type="date"
                                name="end_date"
                                class="schedule-date-native"
                                value="{{ old('end_date', \Carbon\Carbon::parse($event->end_date ?? $event->event_date)->format('Y-m-d')) }}"
                                id="endDateInput"
                            >
                            <div class="schedule-date-display" id="endDateDisplay">
                                {{ old('end_date', \Carbon\Carbon::parse($event->end_date ?? $event->event_date)->format('Y-m-d')) ?: '年 / 月 / 日' }}
                            </div>
                            <span class="schedule-icon">📅</span>
                        </div>
                        @error('end_date')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="width: 120px;">
                        <div class="schedule-time-wrap @error('end_time') input-error @enderror" id="endTimeWrap">
                            <input
                                type="time"
                                name="end_time"
                                class="schedule-time-native"
                                value="{{ old('end_time', $event->end_time) }}"
                                id="endTimeInput"
                            >
                            <div class="schedule-time-display" id="endTimeDisplay">
                                {{ old('end_time', $event->end_time) ?: '--:--' }}
                            </div>
                            <span class="schedule-icon">🕒</span>
                        </div>
                        @error('end_time')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <p id="time-error" class="time-error"></p>

            <div class="form-group">
                <label>色</label>

                <div class="custom-color-picker @error('color') input-error @enderror" style="border-radius: 14px;">
                    <button type="button" class="color-selected" id="colorToggle">
                        <span class="color-left">
                            <span class="color-circle color-{{ $selectedColor }}" id="selectedColorCircle"></span>
                            <span id="selectedColorText">{{ $selectedColorLabel }}</span>
                        </span>
                        <span class="color-arrow">▾</span>
                    </button>

                    <div class="color-dropdown" id="colorDropdown">
                        <button type="button" class="color-item" data-value="green" data-label="エメラルド・グリーン" data-class="color-green">
                            <span class="color-circle color-green"></span>
                            <span>エメラルド・グリーン</span>
                        </button>

                        <button type="button" class="color-item" data-value="blue" data-label="ディープ・スカイブルー" data-class="color-blue">
                            <span class="color-circle color-blue"></span>
                            <span>ディープ・スカイブルー</span>
                        </button>

                        <button type="button" class="color-item" data-value="red" data-label="アップル・レッド" data-class="color-red">
                            <span class="color-circle color-red"></span>
                            <span>アップル・レッド</span>
                        </button>

                        <button type="button" class="color-item" data-value="gray" data-label="グレー" data-class="color-gray">
                            <span class="color-circle color-gray"></span>
                            <span>グレー</span>
                        </button>

                        <button type="button" class="color-item" data-value="yellow" data-label="ブライト・イエロー" data-class="color-yellow">
                            <span class="color-circle color-yellow"></span>
                            <span>ブライト・イエロー</span>
                        </button>
                    </div>

                    <input
                        type="hidden"
                        name="color"
                        id="selectedColorValue"
                        value="{{ old('color', $event->color ?? 'green') }}"
                    >
                </div>
                @error('color')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label>繰り返し</label>

                <button type="button" class="repeat-open-button" id="repeatOpenButton">
                    <span id="repeatSummaryText">繰り返さない</span>
                    <span class="repeat-open-arrow">›</span>
                </button>

                @error('repeat_type')
                    <p class="field-error">{{ $message }}</p>
                @enderror

                <input type="hidden" name="repeat_type" id="repeat_type"
                    value="{{ old('repeat_type', $event->repeat_type ?? 'none') }}">

                <input type="hidden" name="repeat_end_type" id="repeat_end_type"
                    value="{{ old('repeat_end_type', $event->repeat_end_type ?? 'never') }}">

                <input type="hidden" name="repeat_until" id="repeat_until"
                    value="{{ old('repeat_until', !empty($event->repeat_until) ? \Carbon\Carbon::parse($event->repeat_until)->format('Y-m-d') : '') }}">

                <input type="hidden" name="repeat_count" id="repeat_count"
                    value="{{ old('repeat_count', $event->repeat_count ?? '') }}">

                <input type="hidden" name="repeat_month_mode" id="repeat_month_mode"
                    value="{{ old('repeat_month_mode', $event->repeat_month_mode ?? 'same_day') }}">

                <input type="hidden" name="repeat_month_nth" id="repeat_month_nth"
                    value="{{ old('repeat_month_nth', $event->repeat_month_nth ?? '1') }}">

                <input type="hidden" name="repeat_month_weekday" id="repeat_month_weekday"
                    value="{{ old('repeat_month_weekday', $event->repeat_month_weekday ?? 'mon') }}">

                <div id="repeatWeekdaysHiddenWrap" style="display:none;">
                    @foreach($repeatWeekdays as $weekday)
                        <input type="hidden" name="repeat_weekdays[]" value="{{ $weekday }}">
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label for="notification_time">通知</label>
                <select id="notification_time" name="notification_time" class="@error('notification_time') input-error @enderror">
                    <option value="" {{ old('notification_time', $event->notification_time) == '' ? 'selected' : '' }}>通知しない</option>
                    <option value="5" {{ old('notification_time', $event->notification_time) == '5' ? 'selected' : '' }}>5分前</option>
                    <option value="10" {{ old('notification_time', $event->notification_time) == '10' ? 'selected' : '' }}>10分前</option>
                    <option value="30" {{ old('notification_time', $event->notification_time) == '30' ? 'selected' : '' }}>30分前</option>
                    <option value="60" {{ old('notification_time', $event->notification_time) == '60' ? 'selected' : '' }}>1時間前</option>
                    <option value="1440" {{ old('notification_time', $event->notification_time) == '1440' ? 'selected' : '' }}>1日前</option>
                </select>
                @error('notification_time')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">詳細メモ</label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="予定のメモを入力"
                    class="@error('description') input-error @enderror"
                >{{ old('description', $event->description) }}</textarea>
                @error('description')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="repeat-modal" id="repeatModal">
                <div class="repeat-modal-backdrop" id="repeatModalBackdrop"></div>

                <div class="repeat-modal-panel">
                    <div class="repeat-modal-header">
                        <button type="button" class="repeat-back-button" id="repeatModalClose">‹</button>
                        <h3>繰り返し設定</h3>
                    </div>

                    <div class="repeat-tabs">
                        <button type="button" class="repeat-tab" data-repeat-type="yearly">年</button>
                        <button type="button" class="repeat-tab" data-repeat-type="monthly">月</button>
                        <button type="button" class="repeat-tab" data-repeat-type="weekly">週</button>
                        <button type="button" class="repeat-tab" data-repeat-type="daily">日</button>
                    </div>

                    <div class="repeat-modal-body">
                        <div class="repeat-pane" id="repeatPaneDaily">
                            <div class="repeat-row-block">
                                <div class="repeat-row-label">間隔</div>
                                <div class="repeat-inline-box">1日</div>
                            </div>

                            <div class="repeat-row-block">
                                <div class="repeat-row-label">開始日</div>
                                <div class="repeat-row-value" id="repeatStartDateDaily"></div>
                            </div>
                        </div>

                        <div class="repeat-pane" id="repeatPaneWeekly">
                            <div class="repeat-row-block">
                                <div class="repeat-row-label">間隔</div>
                                <div class="repeat-inline-box">1週間</div>
                            </div>

                            <div class="repeat-row-block">
                                <div class="repeat-row-label">曜日</div>
                                <div class="repeat-modal-weekdays" id="repeatModalWeekdays">
                                    <button type="button" class="repeat-modal-weekday" data-weekday="mon">月</button>
                                    <button type="button" class="repeat-modal-weekday" data-weekday="tue">火</button>
                                    <button type="button" class="repeat-modal-weekday" data-weekday="wed">水</button>
                                    <button type="button" class="repeat-modal-weekday" data-weekday="thu">木</button>
                                    <button type="button" class="repeat-modal-weekday" data-weekday="fri">金</button>
                                    <button type="button" class="repeat-modal-weekday" data-weekday="sat">土</button>
                                    <button type="button" class="repeat-modal-weekday" data-weekday="sun">日</button>
                                </div>
                            </div>

                            <div class="repeat-row-block">
                                <div class="repeat-row-label">開始日</div>
                                <div class="repeat-row-value" id="repeatStartDateWeekly"></div>
                            </div>
                        </div>

                        <div class="repeat-pane" id="repeatPaneMonthly">
                            <div class="repeat-row-block">
                                <div class="repeat-row-label">間隔</div>
                                <div class="repeat-inline-box">1カ月</div>
                            </div>

                            <div class="repeat-row-block">
                                <div class="repeat-row-label">基準</div>
                                <div class="repeat-choice-row">
                                    <button type="button" class="repeat-choice-button" id="repeatMonthlySameDayBtn">日付</button>
                                    <button type="button" class="repeat-choice-button" id="repeatMonthlyWeekdayOrderBtn">曜日</button>
                                    <button type="button" class="repeat-choice-button" id="repeatMonthlyMonthEndBtn">月末</button>
                                </div>
                            </div>

                            <div class="repeat-row-block" id="repeatMonthlyWeekdayOrderControls">
                                <div class="repeat-row-label">設定</div>
                                <div class="repeat-choice-selects">
                                    <select id="repeatMonthlyNthSelect">
                                        <option value="1">第1</option>
                                        <option value="2">第2</option>
                                        <option value="3">第3</option>
                                        <option value="4">第4</option>
                                        <option value="last">最終</option>
                                    </select>

                                    <select id="repeatMonthlyWeekdaySelect">
                                        <option value="sun">日曜</option>
                                        <option value="mon">月曜</option>
                                        <option value="tue">火曜</option>
                                        <option value="wed">水曜</option>
                                        <option value="thu">木曜</option>
                                        <option value="fri">金曜</option>
                                        <option value="sat">土曜</option>
                                    </select>
                                </div>
                            </div>

                            <div class="repeat-row-block">
                                <div class="repeat-row-label">開始日</div>
                                <div class="repeat-row-value" id="repeatStartDateMonthly"></div>
                            </div>
                        </div>

                        <div class="repeat-pane" id="repeatPaneYearly">
                            <div class="repeat-row-block">
                                <div class="repeat-row-label">間隔</div>
                                <div class="repeat-inline-box">1年</div>
                            </div>

                            <div class="repeat-row-block">
                                <div class="repeat-row-label">開始日</div>
                                <div class="repeat-row-value" id="repeatStartDateYearly"></div>
                            </div>
                        </div>

                        <div class="repeat-row-block">
                            <div class="repeat-row-label">終了日</div>
                            <div class="repeat-choice-row">
                                <button type="button" class="repeat-choice-button" id="repeatEndNeverBtn">なし</button>
                                <button type="button" class="repeat-choice-button" id="repeatEndDateBtn">指定する</button>
                                <button type="button" class="repeat-choice-button" id="repeatEndCountBtn">回数</button>
                            </div>
                        </div>

                        <div class="repeat-row-block" id="repeatEndDateBlock">
                            <div class="repeat-row-label">終了指定</div>
                            <div class="schedule-date-wrap" id="repeatModalUntilWrap" style="width:100%;">
                                <input type="date" id="repeatModalUntilInput" class="schedule-date-native">
                                <div class="schedule-date-display" id="repeatModalUntilDisplay">年 / 月 / 日</div>
                                <span class="schedule-icon">📅</span>
                            </div>
                        </div>

                        <div class="repeat-row-block" id="repeatEndCountBlock">
                            <div class="repeat-row-label">回数</div>
                            <input type="number" id="repeatModalCountInput" min="1" step="1" class="repeat-count-input" placeholder="10">
                        </div>

                        <div class="repeat-row-block repeat-summary-row">
                            <div class="repeat-row-label">概要</div>
                            <div class="repeat-row-value" id="repeatModalSummary"></div>
                        </div>
                    </div>

                    <div class="repeat-modal-actions">
                        <button type="button" class="repeat-apply-btn" id="repeatApplyButton">設定</button>
                        <button type="button" class="repeat-clear-btn" id="repeatClearButton">クリア</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="danger-zone">
            <p class="danger-zone-title">削除</p>
            <p class="danger-zone-text">
                @if($isRepeating)
                    繰り返し予定は、シリーズ全体またはこの日以降を削除できます。
                @else
                    この予定を削除します。
                @endif
            </p>

            <div class="danger-actions">
                <form method="POST" action="/events/{{ $event->id }}/delete" onsubmit="return confirm('この予定を削除しますか？');">
                    @csrf
                    <button type="submit" class="danger-btn danger-btn-red">
                        {{ $isRepeating ? 'シリーズを削除' : '削除' }}
                    </button>
                </form>

                @if($isRepeating)
                    <form method="POST" action="/events/{{ $event->id }}/delete-future" onsubmit="return confirm('この日以降の繰り返し予定を削除しますか？');">
                        @csrf
                        <input type="hidden" name="occurrence_date" value="{{ $occurrenceDate }}">
                        <button type="submit" class="danger-btn danger-btn-orange">この日以降を削除</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script src="{{ asset('js/event-form.js') }}"></script>
</body>
</html>