document.addEventListener('DOMContentLoaded', function () {
    const colorToggle = document.getElementById('colorToggle');
    const colorDropdown = document.getElementById('colorDropdown');
    const selectedColorValue = document.getElementById('selectedColorValue');
    const selectedColorText = document.getElementById('selectedColorText');
    const selectedColorCircle = document.getElementById('selectedColorCircle');
    const colorItems = document.querySelectorAll('.color-item');

    const colorMap = {
        green: { label: 'エメラルド・グリーン', className: 'color-green' },
        blue: { label: 'ディープ・スカイブルー', className: 'color-blue' },
        red: { label: 'アップル・レッド', className: 'color-red' },
        gray: { label: 'グレー', className: 'color-gray' },
        yellow: { label: 'ブライト・イエロー', className: 'color-yellow' }
    };

    if (selectedColorValue && selectedColorText && selectedColorCircle) {
        const currentColor = selectedColorValue.value || 'green';
        if (colorMap[currentColor]) {
            selectedColorText.textContent = colorMap[currentColor].label;
            selectedColorCircle.className = 'color-circle ' + colorMap[currentColor].className;
        }
    }

    if (colorToggle && colorDropdown) {
        colorToggle.addEventListener('click', function () {
            colorDropdown.classList.toggle('show');
        });

        colorItems.forEach(item => {
            item.addEventListener('click', function () {
                const value = this.dataset.value;
                const label = this.dataset.label;
                const colorClass = this.dataset.class;

                if (selectedColorValue) selectedColorValue.value = value;
                if (selectedColorText) selectedColorText.textContent = label;
                if (selectedColorCircle) selectedColorCircle.className = 'color-circle ' + colorClass;

                colorDropdown.classList.remove('show');
            });
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.custom-color-picker')) {
                colorDropdown.classList.remove('show');
            }
        });
    }

    const bindDisplay = (inputId, displayId, emptyText) => {
        const input = document.getElementById(inputId);
        const display = document.getElementById(displayId);
        if (!input || !display) return;

        const updateText = () => {
            display.textContent = input.value || emptyText;
        };

        updateText();
        input.addEventListener('change', updateText);
        input.addEventListener('input', updateText);
    };

    bindDisplay('startDateInput', 'startDateDisplay', '年 / 月 / 日');
    bindDisplay('startTimeInput', 'startTimeDisplay', '--:--');
    bindDisplay('endDateInput', 'endDateDisplay', '年 / 月 / 日');
    bindDisplay('endTimeInput', 'endTimeDisplay', '--:--');

    const openPicker = (wrapId, inputId) => {
        const wrap = document.getElementById(wrapId);
        const input = document.getElementById(inputId);

        if (!wrap || !input) return;

        wrap.addEventListener('click', function () {
            if (typeof input.showPicker === 'function') {
                input.showPicker();
            } else {
                input.focus();
                input.click();
            }
        });
    };

    openPicker('startDateWrap', 'startDateInput');
    openPicker('startTimeWrap', 'startTimeInput');
    openPicker('endDateWrap', 'endDateInput');
    openPicker('endTimeWrap', 'endTimeInput');

    const startDateInput = document.getElementById('startDateInput');
    const startTimeInput = document.getElementById('startTimeInput');
    const endDateInput = document.getElementById('endDateInput');
    const endTimeInput = document.getElementById('endTimeInput');
    const timeError = document.getElementById('time-error');
    const eventForm = document.getElementById('eventForm') || document.getElementById('eventEditForm');

    if (startDateInput && startTimeInput && endDateInput && endTimeInput && eventForm) {
        let endTouched = false;

        function formatDate(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function formatTime(date) {
            const h = String(date.getHours()).padStart(2, '0');
            const m = String(date.getMinutes()).padStart(2, '0');
            return `${h}:${m}`;
        }

        function updateEndAutomatically() {
            if (!startDateInput.value || !startTimeInput.value || endTouched) return;

            const start = new Date(`${startDateInput.value}T${startTimeInput.value}`);
            start.setHours(start.getHours() + 1);

            endDateInput.value = formatDate(start);
            endTimeInput.value = formatTime(start);

            endDateInput.dispatchEvent(new Event('change'));
            endTimeInput.dispatchEvent(new Event('change'));
        }

        function validateDateTime() {
            const startDate = startDateInput.value;
            const startTime = startTimeInput.value;
            const endDate = endDateInput.value;
            const endTime = endTimeInput.value;

            if (!startDate || !startTime || !endDate || !endTime) {
                if (timeError) timeError.textContent = '';
                endTimeInput.setCustomValidity('');
                return true;
            }

            const start = new Date(`${startDate}T${startTime}`);
            const end = new Date(`${endDate}T${endTime}`);

            if (end <= start) {
                if (timeError) {
                    timeError.textContent = '終了時間は開始時間より後にしてください。';
                }
                endTimeInput.setCustomValidity('終了時間は開始時間より後にしてください。');
                return false;
            }

            if (timeError) timeError.textContent = '';
            endTimeInput.setCustomValidity('');
            return true;
        }

        endDateInput.addEventListener('change', function () {
            endTouched = true;
            validateDateTime();
        });

        endTimeInput.addEventListener('change', function () {
            endTouched = true;
            validateDateTime();
        });

        startDateInput.addEventListener('change', function () {
            updateEndAutomatically();
            validateDateTime();
            updateRepeatDates();
            updateRepeatSummary();
        });

        startTimeInput.addEventListener('change', function () {
            updateEndAutomatically();
            validateDateTime();
        });

        [startDateInput, startTimeInput, endDateInput, endTimeInput].forEach(input => {
            input.addEventListener('input', validateDateTime);
        });

        eventForm.addEventListener('submit', function (e) {
            if (!validateDateTime()) {
                e.preventDefault();
                endTimeInput.reportValidity();
            }
        });
    }

    const repeatOpenButton = document.getElementById('repeatOpenButton');
    const repeatSummaryText = document.getElementById('repeatSummaryText');
    const repeatModal = document.getElementById('repeatModal');
    const repeatModalBackdrop = document.getElementById('repeatModalBackdrop');
    const repeatModalClose = document.getElementById('repeatModalClose');
    const repeatApplyButton = document.getElementById('repeatApplyButton');
    const repeatClearButton = document.getElementById('repeatClearButton');

    const repeatTypeInput = document.getElementById('repeat_type');
    const repeatEndTypeInput = document.getElementById('repeat_end_type');
    const repeatUntilInput = document.getElementById('repeat_until');
    const repeatCountInput = document.getElementById('repeat_count');
    const repeatMonthModeInput = document.getElementById('repeat_month_mode');
    const repeatMonthNthInput = document.getElementById('repeat_month_nth');
    const repeatMonthWeekdayInput = document.getElementById('repeat_month_weekday');
    const repeatWeekdaysHiddenWrap = document.getElementById('repeatWeekdaysHiddenWrap');

    const repeatTabs = document.querySelectorAll('.repeat-tab');
    const repeatPanes = document.querySelectorAll('.repeat-pane');

    const repeatMonthlySameDayBtn = document.getElementById('repeatMonthlySameDayBtn');
    const repeatMonthlyWeekdayOrderBtn = document.getElementById('repeatMonthlyWeekdayOrderBtn');
    const repeatMonthlyMonthEndBtn = document.getElementById('repeatMonthlyMonthEndBtn');
    const repeatMonthlyWeekdayOrderControls = document.getElementById('repeatMonthlyWeekdayOrderControls');
    const repeatMonthlyNthSelect = document.getElementById('repeatMonthlyNthSelect');
    const repeatMonthlyWeekdaySelect = document.getElementById('repeatMonthlyWeekdaySelect');

    const repeatEndNeverBtn = document.getElementById('repeatEndNeverBtn');
    const repeatEndDateBtn = document.getElementById('repeatEndDateBtn');
    const repeatEndCountBtn = document.getElementById('repeatEndCountBtn');
    const repeatEndDateBlock = document.getElementById('repeatEndDateBlock');
    const repeatEndCountBlock = document.getElementById('repeatEndCountBlock');

    const repeatModalUntilInput = document.getElementById('repeatModalUntilInput');
    const repeatModalUntilDisplay = document.getElementById('repeatModalUntilDisplay');
    const repeatModalCountInput = document.getElementById('repeatModalCountInput');
    const repeatModalSummary = document.getElementById('repeatModalSummary');
    const repeatModalWeekdayButtons = document.querySelectorAll('.repeat-modal-weekday');

    const repeatStartDateDaily = document.getElementById('repeatStartDateDaily');
    const repeatStartDateWeekly = document.getElementById('repeatStartDateWeekly');
    const repeatStartDateMonthly = document.getElementById('repeatStartDateMonthly');
    const repeatStartDateYearly = document.getElementById('repeatStartDateYearly');

    if (
        repeatOpenButton &&
        repeatSummaryText &&
        repeatModal &&
        repeatTypeInput &&
        repeatEndTypeInput &&
        repeatUntilInput &&
        repeatCountInput &&
        repeatMonthModeInput &&
        repeatMonthNthInput &&
        repeatMonthWeekdayInput &&
        repeatWeekdaysHiddenWrap
    ) {
        const state = {
            repeatType: repeatTypeInput.value || 'none',
            weekdays: Array.from(
                repeatWeekdaysHiddenWrap.querySelectorAll('input[name="repeat_weekdays[]"]')
            ).map(input => input.value),
            monthMode: repeatMonthModeInput.value || 'same_day',
            monthNth: repeatMonthNthInput.value || '1',
            monthWeekday: repeatMonthWeekdayInput.value || 'mon',
            endType: repeatEndTypeInput.value || 'never',
            until: repeatUntilInput.value || '',
            count: repeatCountInput.value || ''
        };

        if (repeatModalUntilInput) {
            repeatModalUntilInput.value = state.until;
        }

        if (repeatModalCountInput) {
            repeatModalCountInput.value = state.count;
        }

        if (repeatMonthlyNthSelect) {
            repeatMonthlyNthSelect.value = state.monthNth;
        }

        if (repeatMonthlyWeekdaySelect) {
            repeatMonthlyWeekdaySelect.value = state.monthWeekday;
        }

        function formatStartDateText() {
            if (!startDateInput || !startDateInput.value) return '';
            const date = new Date(startDateInput.value + 'T00:00:00');
            const weekdays = ['日', '月', '火', '水', '木', '金', '土'];
            return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日[${weekdays[date.getDay()]}]`;
        }

        function dayKeyToLabel(key) {
            const map = {
                mon: '月曜日',
                tue: '火曜日',
                wed: '水曜日',
                thu: '木曜日',
                fri: '金曜日',
                sat: '土曜日',
                sun: '日曜日',
            };
            return map[key] || '';
        }

        function nthToLabel(nth) {
            const map = {
                '1': '第1',
                '2': '第2',
                '3': '第3',
                '4': '第4',
                'last': '最終',
            };
            return map[nth] || '';
        }

        function updateRepeatDates() {
            const text = formatStartDateText();
            if (repeatStartDateDaily) repeatStartDateDaily.textContent = text;
            if (repeatStartDateWeekly) repeatStartDateWeekly.textContent = text;
            if (repeatStartDateMonthly) repeatStartDateMonthly.textContent = text;
            if (repeatStartDateYearly) repeatStartDateYearly.textContent = text;
        }

        function updateUntilDisplay() {
            if (!repeatModalUntilDisplay || !repeatModalUntilInput) return;
            repeatModalUntilDisplay.textContent = repeatModalUntilInput.value || '年 / 月 / 日';
        }

        function setRepeatType(type) {
            state.repeatType = type;

            repeatTabs.forEach(tab => {
                tab.classList.toggle('active', tab.dataset.repeatType === type);
            });

            repeatPanes.forEach(pane => pane.classList.remove('active'));

            if (type === 'daily') document.getElementById('repeatPaneDaily')?.classList.add('active');
            if (type === 'weekly') document.getElementById('repeatPaneWeekly')?.classList.add('active');
            if (type === 'monthly') document.getElementById('repeatPaneMonthly')?.classList.add('active');
            if (type === 'yearly') document.getElementById('repeatPaneYearly')?.classList.add('active');

            updateRepeatSummary();
        }

        function setMonthMode(mode) {
            state.monthMode = mode;

            repeatMonthlySameDayBtn?.classList.toggle('active', mode === 'same_day');
            repeatMonthlyWeekdayOrderBtn?.classList.toggle('active', mode === 'weekday_order');
            repeatMonthlyMonthEndBtn?.classList.toggle('active', mode === 'month_end');

            if (repeatMonthlyWeekdayOrderControls) {
                repeatMonthlyWeekdayOrderControls.style.display = mode === 'weekday_order' ? 'block' : 'none';
            }

            updateRepeatSummary();
        }

        function setEndType(type) {
            state.endType = type;

            repeatEndNeverBtn?.classList.toggle('active', type === 'never');
            repeatEndDateBtn?.classList.toggle('active', type === 'date');
            repeatEndCountBtn?.classList.toggle('active', type === 'count');

            if (repeatEndDateBlock) {
                repeatEndDateBlock.style.display = type === 'date' ? 'block' : 'none';
            }

            if (repeatEndCountBlock) {
                repeatEndCountBlock.style.display = type === 'count' ? 'block' : 'none';
            }
        }

        function syncWeekdayButtons() {
            repeatModalWeekdayButtons.forEach(btn => {
                btn.classList.toggle('active', state.weekdays.includes(btn.dataset.weekday));
            });
        }

        function buildSummary() {
            if (state.repeatType === 'none') {
                return '繰り返さない';
            }

            if (state.repeatType === 'daily') {
                return '毎日';
            }

            if (state.repeatType === 'weekly') {
                if (!state.weekdays.length) {
                    return '毎週';
                }
                const order = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                const sorted = order.filter(day => state.weekdays.includes(day));
                return '毎週 ' + sorted.map(dayKeyToLabel).join('・');
            }

            if (state.repeatType === 'monthly') {
                if (state.monthMode === 'same_day') {
                    if (!startDateInput?.value) return '毎月';
                    const d = new Date(startDateInput.value + 'T00:00:00');
                    return `毎月${d.getDate()}日`;
                }

                if (state.monthMode === 'weekday_order') {
                    return `毎月 ${nthToLabel(state.monthNth)}${dayKeyToLabel(state.monthWeekday)}`;
                }

                if (state.monthMode === 'month_end') {
                    return '毎月末';
                }

                return '毎月';
            }

            if (state.repeatType === 'yearly') {
                if (!startDateInput?.value) return '毎年';
                const d = new Date(startDateInput.value + 'T00:00:00');
                return `毎年${d.getMonth() + 1}月${d.getDate()}日`;
            }

            return '繰り返さない';
        }

        function updateRepeatSummary() {
            const summary = buildSummary();
            if (repeatSummaryText) repeatSummaryText.textContent = summary;
            if (repeatModalSummary) repeatModalSummary.textContent = summary;
            updateRepeatDates();
        }

        function openRepeatModal() {
            repeatModal.classList.add('show');
            updateRepeatDates();
            updateUntilDisplay();
            syncWeekdayButtons();
            updateRepeatSummary();
            document.body.style.overflow = 'hidden';
        }

        function closeRepeatModal() {
            repeatModal.classList.remove('show');
            document.body.style.overflow = '';
        }

        function applyRepeatToHiddenInputs() {
            repeatTypeInput.value = state.repeatType;
            repeatEndTypeInput.value = state.endType;
            repeatUntilInput.value = repeatModalUntilInput ? repeatModalUntilInput.value : '';
            repeatCountInput.value = repeatModalCountInput ? repeatModalCountInput.value : '';
            repeatMonthModeInput.value = state.monthMode;
            repeatMonthNthInput.value = repeatMonthlyNthSelect ? repeatMonthlyNthSelect.value : state.monthNth;
            repeatMonthWeekdayInput.value = repeatMonthlyWeekdaySelect ? repeatMonthlyWeekdaySelect.value : state.monthWeekday;

            repeatWeekdaysHiddenWrap.innerHTML = '';
            state.weekdays.forEach(weekday => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'repeat_weekdays[]';
                input.value = weekday;
                repeatWeekdaysHiddenWrap.appendChild(input);
            });
        }

        function clearRepeatState() {
            state.repeatType = 'none';
            state.weekdays = [];
            state.monthMode = 'same_day';
            state.monthNth = '1';
            state.monthWeekday = 'mon';
            state.endType = 'never';

            if (repeatModalUntilInput) repeatModalUntilInput.value = '';
            if (repeatModalCountInput) repeatModalCountInput.value = '';
            if (repeatMonthlyNthSelect) repeatMonthlyNthSelect.value = '1';
            if (repeatMonthlyWeekdaySelect) repeatMonthlyWeekdaySelect.value = 'mon';

            setRepeatType('daily');
            setMonthMode('same_day');
            setEndType('never');
            syncWeekdayButtons();
            updateUntilDisplay();

            state.repeatType = 'none';
            applyRepeatToHiddenInputs();
            updateRepeatSummary();
            closeRepeatModal();
        }

        repeatOpenButton.addEventListener('click', openRepeatModal);
        repeatModalBackdrop?.addEventListener('click', closeRepeatModal);
        repeatModalClose?.addEventListener('click', closeRepeatModal);

        repeatTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                setRepeatType(this.dataset.repeatType);
            });
        });

        repeatMonthlySameDayBtn?.addEventListener('click', () => setMonthMode('same_day'));
        repeatMonthlyWeekdayOrderBtn?.addEventListener('click', () => setMonthMode('weekday_order'));
        repeatMonthlyMonthEndBtn?.addEventListener('click', () => setMonthMode('month_end'));

        repeatMonthlyNthSelect?.addEventListener('change', function () {
            state.monthNth = this.value;
            updateRepeatSummary();
        });

        repeatMonthlyWeekdaySelect?.addEventListener('change', function () {
            state.monthWeekday = this.value;
            updateRepeatSummary();
        });

        repeatEndNeverBtn?.addEventListener('click', () => setEndType('never'));
        repeatEndDateBtn?.addEventListener('click', () => setEndType('date'));
        repeatEndCountBtn?.addEventListener('click', () => setEndType('count'));

        repeatModalUntilInput?.addEventListener('change', updateUntilDisplay);
        repeatModalUntilInput?.addEventListener('input', updateUntilDisplay);

        document.getElementById('repeatModalUntilWrap')?.addEventListener('click', function () {
            if (!repeatModalUntilInput) return;
            if (typeof repeatModalUntilInput.showPicker === 'function') {
                repeatModalUntilInput.showPicker();
            } else {
                repeatModalUntilInput.focus();
                repeatModalUntilInput.click();
            }
        });

        repeatModalWeekdayButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const value = this.dataset.weekday;

                if (state.weekdays.includes(value)) {
                    state.weekdays = state.weekdays.filter(v => v !== value);
                } else {
                    state.weekdays.push(value);
                }

                syncWeekdayButtons();
                updateRepeatSummary();
            });
        });

        repeatApplyButton?.addEventListener('click', function () {
            state.until = repeatModalUntilInput ? repeatModalUntilInput.value : '';
            state.count = repeatModalCountInput ? repeatModalCountInput.value : '';
            state.monthNth = repeatMonthlyNthSelect ? repeatMonthlyNthSelect.value : state.monthNth;
            state.monthWeekday = repeatMonthlyWeekdaySelect ? repeatMonthlyWeekdaySelect.value : state.monthWeekday;

            applyRepeatToHiddenInputs();
            updateRepeatSummary();
            closeRepeatModal();
        });

        repeatClearButton?.addEventListener('click', clearRepeatState);

        if (state.repeatType === 'none') {
            setRepeatType('daily');
            state.repeatType = 'none';
        } else {
            setRepeatType(state.repeatType);
        }

        setMonthMode(state.monthMode);
        setEndType(state.endType);
        syncWeekdayButtons();
        updateUntilDisplay();
        applyRepeatToHiddenInputs();
        updateRepeatSummary();
    }
});