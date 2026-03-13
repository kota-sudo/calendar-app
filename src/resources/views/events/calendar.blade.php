<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カレンダー</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1a73e8">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Calendar">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <link rel="stylesheet" href="/css/calendar.css">
</head>
<body>

    <div class="calendar-header-row">
        <button type="button" class="calendar-title-button" id="openMonthPicker">
            <span id="calendarTitleText">{{ $currentYear }}年 {{ $currentMonth }}月</span>
            <span class="calendar-title-arrow">▼</span>
        </button>

        <div class="calendar-tools">
            <a href="/calendar?year={{ now()->year }}&month={{ now()->month }}@if($calendarId)&calendar_id={{ $calendarId }}@endif" class="today-button">
                今日
            </a>

            <button type="button" id="notificationToggle" class="notification-switch off" aria-pressed="false">
                <span class="switch-track">
                    <span class="switch-thumb"></span>
                </span>
                <span class="switch-label">OFF</span>
            </button>

            <button type="button" id="calendarFilterBtn" class="calendar-filter-button">
                ☰
            </button>
        </div>
    </div>

    <div class="today-summary-card">
        <div class="today-summary-header">
            <div class="today-summary-title-row">
                <p class="today-summary-label">今日の予定</p>
                <h2 class="today-summary-date">{{ \Carbon\Carbon::parse($today)->format('n月j日') }}</h2>
            </div>

            <div class="today-summary-actions">
                <a href="/calendar/day?date={{ $today }}@if($calendarId)&calendar_id={{ $calendarId }}@endif" class="today-summary-link">もっと見る</a>

                <button type="button" class="today-summary-toggle" id="todaySummaryToggle" aria-expanded="true">
                    <span id="todaySummaryToggleText">閉じる</span>
                    <span class="today-summary-toggle-icon">▾</span>
                </button>
            </div>
        </div>

        <div class="today-summary-content" id="todaySummaryContent">
            @if($todayEvents->isEmpty())
                <p class="today-summary-empty">今日の予定はありません</p>
            @else
                <div class="today-summary-list">
                    @foreach($todayEvents as $event)
                        <a href="/events/{{ $event->id }}/edit?occurrence_date={{ $today }}@if($calendarId)&calendar_id={{ $calendarId }}@endif" class="today-summary-item">
                            <span class="today-summary-time">
                                {{ $event->start_time ? \Carbon\Carbon::parse($event->start_time)->format('H:i') : '--:--' }}
                            </span>
                            <span class="today-summary-title">{{ $event->title }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="month-nav">
        <a href="/calendar?year={{ $prevMonth->year }}&month={{ $prevMonth->month }}@if($calendarId)&calendar_id={{ $calendarId }}@endif">前月</a>
        <a href="/calendar?year={{ $nextMonth->year }}&month={{ $nextMonth->month }}@if($calendarId)&calendar_id={{ $calendarId }}@endif">次月</a>
    </div>

    <div id="calendarSwipeArea" class="calendar-swipe-area">
        <table class="calendar-table">
            <tr>
                <th class="weekday-header sunday-header">日</th>
                <th class="weekday-header">月</th>
                <th class="weekday-header">火</th>
                <th class="weekday-header">水</th>
                <th class="weekday-header">木</th>
                <th class="weekday-header">金</th>
                <th class="weekday-header saturday-header">土</th>
            </tr>

            @php
                $firstDate = \Carbon\Carbon::create($currentYear, $currentMonth, 1);
                $lastDate = $firstDate->copy()->endOfMonth();

                $leadingDays = $firstDate->dayOfWeek;
                $daysInMonth = $lastDate->day;
                $trailingDays = 6 - $lastDate->dayOfWeek;

                $totalCells = $leadingDays + $daysInMonth + $trailingDays;
                $weekCount = (int) ceil($totalCells / 7);

                $calendarStart = $firstDate->copy()->subDays($leadingDays);
            @endphp

            @for ($week = 0; $week < $weekCount; $week++)
                <tr>
                    @for ($day = 0; $day < 7; $day++)
                        @php
                            $cellDate = $calendarStart->copy()->addDays($week * 7 + $day);
                            $dateString = $cellDate->format('Y-m-d');

                            $isCurrentMonth = $cellDate->month == $currentMonth;
                            $isSunday = $cellDate->dayOfWeek === 0;
                            $isSaturday = $cellDate->dayOfWeek === 6;
                            $isHoliday = in_array($dateString, $holidayDates ?? []);
                            $holidayName = $holidayNames[$dateString] ?? null;
                            $isSelected = ($selectedDate ?? null) === $dateString;
                            $isToday = now()->toDateString() === $dateString;
                        @endphp

                        <td
                            class="calendar-cell
                                {{ !$isCurrentMonth ? 'other-month-cell' : '' }}
                                {{ $isSunday || $isHoliday ? 'holiday-cell' : '' }}
                                {{ $isSaturday ? 'saturday-cell' : '' }}
                                {{ $isSelected ? 'selected-cell' : '' }}"
                            data-date="{{ $dateString }}"
                            onclick="location.href='/calendar/day?date={{ $dateString }}@if($calendarId)&calendar_id={{ $calendarId }}@endif'"
                        >
                            <div
                                class="day-number
                                    {{ !$isCurrentMonth ? 'other-month-text' : '' }}
                                    {{ $isSunday || $isHoliday ? 'holiday-text' : '' }}
                                    {{ $isSaturday ? 'saturday-text' : '' }}
                                    {{ $isToday ? 'today-number' : '' }}"
                            >
                                {{ $cellDate->day }}
                            </div>

                            @if($holidayName)
                                <div class="holiday-name {{ !$isCurrentMonth ? 'other-month-text' : '' }}">
                                    {{ $holidayName }}
                                </div>
                            @endif

                            @if(isset($events[$dateString]))
                                @foreach($events[$dateString] as $event)
                                    <a href="/events/{{ $event->id }}/edit?occurrence_date={{ $dateString }}@if($calendarId)&calendar_id={{ $calendarId }}@endif"
                                       class="event-item-link"
                                       onclick="event.stopPropagation();">
                                        <div
                                            class="event-item event-{{ $event->color }}"
                                            draggable="true"
                                            data-event-id="{{ $event->id }}"
                                            ondragstart="event.stopPropagation();"
                                        >
                                            @if($event->start_time)
                                                {{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}
                                            @endif
                                            {{ $event->title }}
                                        </div>
                                    </a>
                                @endforeach
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        </table>
    </div>

    <div class="month-picker-modal" id="monthPickerModal">
        <div class="month-picker-backdrop" id="monthPickerBackdrop"></div>

        <div class="month-picker-panel">
            <div class="month-picker-header">
                <h2>年月を選択</h2>
                <button type="button" class="month-picker-close" id="closeMonthPicker">×</button>
            </div>

            <div class="month-wheel">
                <div class="wheel-column" id="yearWheel">
                    @for ($year = $currentYear - 5; $year <= $currentYear + 5; $year++)
                        <div class="wheel-item {{ $year == $currentYear ? 'active' : '' }}" data-year="{{ $year }}">
                            {{ $year }}年
                        </div>
                    @endfor
                </div>

                <div class="wheel-column" id="monthWheel">
                    @for ($month = 1; $month <= 12; $month++)
                        <div class="wheel-item {{ $month == $currentMonth ? 'active' : '' }}" data-month="{{ $month }}">
                            {{ $month }}月
                        </div>
                    @endfor
                </div>
            </div>

            <div class="month-picker-actions">
                <button type="button" class="month-picker-cancel" id="cancelMonthPicker">キャンセル</button>
                <button type="button" class="month-picker-submit" id="submitMonthPicker">移動</button>
            </div>
        </div>
    </div>

    <div id="calendarFilterModal" class="calendar-filter-modal">
        <div class="calendar-filter-backdrop" id="calendarFilterBackdrop"></div>

        <div class="calendar-filter-panel">
            <div class="calendar-filter-header">
                <h3>カレンダー選択</h3>
                <button type="button" id="closeFilter" class="calendar-filter-close">×</button>
            </div>

            <a href="/calendar?year={{ $currentYear }}&month={{ $currentMonth }}"
               class="calendar-filter-link {{ empty($calendarId) ? 'active' : '' }}">
                すべて
            </a>

           @foreach($calendars as $calendar)
    <div class="calendar-filter-row">
        <a href="/calendar?year={{ $currentYear }}&month={{ $currentMonth }}&calendar_id={{ $calendar->id }}"
           class="calendar-filter-link calendar-filter-main-link {{ (string)$calendarId === (string)$calendar->id ? 'active' : '' }}">
            {{ $calendar->name }}
        </a>

        @if($calendar->owner_user_id === auth()->id())
            <div class="calendar-filter-action-links">
                <a href="{{ route('calendars.edit', $calendar->id) }}" class="calendar-filter-sub-link">
                    名前変更
                </a>

                <a href="{{ route('calendars.share.edit', $calendar->id) }}" class="calendar-filter-sub-link">
                    共有
                </a>
            </div>
        @endif
    </div>
@endforeach
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const swipeArea = document.getElementById('calendarSwipeArea');
        if (swipeArea) {
            let startX = 0;
            let startY = 0;
            let endX = 0;
            let endY = 0;

            const prevUrl = "/calendar?year={{ $prevMonth->year }}&month={{ $prevMonth->month }}@if($calendarId)&calendar_id={{ $calendarId }}@endif";
            const nextUrl = "/calendar?year={{ $nextMonth->year }}&month={{ $nextMonth->month }}@if($calendarId)&calendar_id={{ $calendarId }}@endif";

            swipeArea.addEventListener('touchstart', function (e) {
                const touch = e.changedTouches[0];
                startX = touch.screenX;
                startY = touch.screenY;
            }, { passive: true });

            swipeArea.addEventListener('touchend', function (e) {
                const touch = e.changedTouches[0];
                endX = touch.screenX;
                endY = touch.screenY;
                handleSwipe();
            }, { passive: true });

            function handleSwipe() {
                const diffX = endX - startX;
                const diffY = endY - startY;
                const minSwipeDistance = 70;

                if (Math.abs(diffX) < minSwipeDistance) return;
                if (Math.abs(diffY) > Math.abs(diffX)) return;

                if (diffX > 0) {
                    location.href = prevUrl;
                } else {
                    location.href = nextUrl;
                }
            }
        }

        const openBtn = document.getElementById('openMonthPicker');
        const monthPickerModal = document.getElementById('monthPickerModal');
        const monthPickerBackdrop = document.getElementById('monthPickerBackdrop');
        const closeMonthPickerBtn = document.getElementById('closeMonthPicker');
        const cancelMonthPickerBtn = document.getElementById('cancelMonthPicker');
        const submitMonthPickerBtn = document.getElementById('submitMonthPicker');

        const yearWheel = document.getElementById('yearWheel');
        const monthWheel = document.getElementById('monthWheel');

        let selectedYear = {{ $currentYear }};
        let selectedMonth = {{ $currentMonth }};
        const currentCalendarId = @json($calendarId);

        function centerItemInWheel(wheel, item) {
            if (!wheel || !item) return;

            const itemCenter = item.offsetTop + (item.offsetHeight / 2);
            const wheelCenter = wheel.clientHeight / 2;

            wheel.scrollTo({
                top: itemCenter - wheelCenter,
                behavior: 'smooth'
            });
        }

        function setActiveItem(wheelSelector, itemSelector, targetItem) {
            document.querySelectorAll(`${wheelSelector} ${itemSelector}`).forEach(i => {
                i.classList.remove('active');
            });

            targetItem.classList.add('active');
        }

        function syncWheelToCurrentSelection() {
            const activeYear = document.querySelector(`#yearWheel .wheel-item[data-year="${selectedYear}"]`);
            const activeMonth = document.querySelector(`#monthWheel .wheel-item[data-month="${selectedMonth}"]`);

            if (activeYear) {
                setActiveItem('#yearWheel', '.wheel-item', activeYear);
                centerItemInWheel(yearWheel, activeYear);
            }

            if (activeMonth) {
                setActiveItem('#monthWheel', '.wheel-item', activeMonth);
                centerItemInWheel(monthWheel, activeMonth);
            }
        }

        function openMonthModal() {
            if (!monthPickerModal) return;
            monthPickerModal.classList.add('show');
            setTimeout(() => {
                syncWheelToCurrentSelection();
            }, 30);
        }

        function closeMonthModal() {
            if (!monthPickerModal) return;
            monthPickerModal.classList.remove('show');
        }

        if (openBtn) openBtn.addEventListener('click', openMonthModal);
        if (monthPickerBackdrop) monthPickerBackdrop.addEventListener('click', closeMonthModal);
        if (closeMonthPickerBtn) closeMonthPickerBtn.addEventListener('click', closeMonthModal);
        if (cancelMonthPickerBtn) cancelMonthPickerBtn.addEventListener('click', closeMonthModal);

        document.querySelectorAll("#yearWheel .wheel-item").forEach(item => {
            item.addEventListener("click", () => {
                setActiveItem('#yearWheel', '.wheel-item', item);
                selectedYear = item.dataset.year;
                centerItemInWheel(yearWheel, item);
            });
        });

        document.querySelectorAll("#monthWheel .wheel-item").forEach(item => {
            item.addEventListener("click", () => {
                setActiveItem('#monthWheel', '.wheel-item', item);
                selectedMonth = item.dataset.month;
                centerItemInWheel(monthWheel, item);
            });
        });

        if (submitMonthPickerBtn) {
            submitMonthPickerBtn.addEventListener('click', function () {
                let url = `/calendar?year=${selectedYear}&month=${selectedMonth}`;
                if (currentCalendarId) {
                    url += `&calendar_id=${currentCalendarId}`;
                }
                location.href = url;
            });
        }

        const todaySummaryCard = document.querySelector('.today-summary-card');
        const todaySummaryToggle = document.getElementById('todaySummaryToggle');
        const todaySummaryToggleText = document.getElementById('todaySummaryToggleText');

        function setTodaySummaryState(collapsed) {
            if (!todaySummaryCard || !todaySummaryToggle || !todaySummaryToggleText) return;

            if (collapsed) {
                todaySummaryCard.classList.add('collapsed');
                todaySummaryToggle.setAttribute('aria-expanded', 'false');
                todaySummaryToggleText.textContent = '開く';
                localStorage.setItem('calendar_today_summary_collapsed', '1');
            } else {
                todaySummaryCard.classList.remove('collapsed');
                todaySummaryToggle.setAttribute('aria-expanded', 'true');
                todaySummaryToggleText.textContent = '閉じる';
                localStorage.setItem('calendar_today_summary_collapsed', '0');
            }
        }

        if (todaySummaryToggle) {
            const collapsed = localStorage.getItem('calendar_today_summary_collapsed') === '1';
            setTodaySummaryState(collapsed);

            todaySummaryToggle.addEventListener('click', function () {
                const isCollapsed = todaySummaryCard.classList.contains('collapsed');
                setTodaySummaryState(!isCollapsed);
            });
        }

        const filterBtn = document.getElementById('calendarFilterBtn');
        const filterModal = document.getElementById('calendarFilterModal');
        const filterBackdrop = document.getElementById('calendarFilterBackdrop');
        const closeFilterBtn = document.getElementById('closeFilter');

        function openFilterModal() {
            if (filterModal) {
                filterModal.classList.add('show');
            }
        }

        function closeFilterModal() {
            if (filterModal) {
                filterModal.classList.remove('show');
            }
        }

        if (filterBtn) {
            filterBtn.addEventListener('click', openFilterModal);
        }

        if (filterBackdrop) {
            filterBackdrop.addEventListener('click', closeFilterModal);
        }

        if (closeFilterBtn) {
            closeFilterBtn.addEventListener('click', closeFilterModal);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeMonthModal();
                closeFilterModal();
            }
        });
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let draggedEventId = null;

        document.querySelectorAll('.event-item').forEach(item => {
            item.addEventListener('dragstart', function (e) {
                draggedEventId = this.dataset.eventId;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            item.addEventListener('dragend', function () {
                this.classList.remove('dragging');
            });
        });

        document.querySelectorAll('.calendar-cell').forEach(cell => {
            cell.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            cell.addEventListener('dragleave', function () {
                this.classList.remove('drag-over');
            });

            cell.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                const newDate = this.dataset.date;

                if (!draggedEventId || !newDate) {
                    return;
                }

                fetch(`/events/${draggedEventId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        new_date: newDate
                    })
                })
                .then(async response => {
                    if (!response.ok) {
                        const text = await response.text();
                        throw new Error(text || '移動に失敗しました');
                    }
                    return response.json();
                })
                .then(() => {
                    location.reload();
                })
                .catch(error => {
                    console.error(error);
                    alert('予定の移動に失敗しました。');
                });
            });
        });
    });
    </script>

    <script src="/js/browser-notifications.js"></script>

    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            // navigator.serviceWorker.register('/sw.js')
            //     .then(function (registration) {
            //         console.log('Service Worker registered:', registration.scope);
            //     })
            //     .catch(function (error) {
            //         console.log('Service Worker registration failed:', error);
            //     });
        });
    }
    </script>

    <a href="/events/create?date={{ now()->toDateString() }}@if($calendarId)&calendar_id={{ $calendarId }}@endif"
       class="calendar-add-button">
        +
    </a>

</body>
</html>