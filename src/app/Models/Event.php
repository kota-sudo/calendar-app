<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'user_id',
        'calendar_id',
        'title',
        'description',
        'event_date',
        'end_date',
        'start_time',
        'end_time',
        'color',
        'calendar_type',
        'notification_time',

        'repeat_type',
        'repeat_weekdays',
        'repeat_month_mode',
        'repeat_month_nth',
        'repeat_month_weekday',
        'repeat_end_type',
        'repeat_until',
        'repeat_count',
    ];

    protected $casts = [
        'event_date' => 'date',
        'end_date' => 'date',
        'repeat_until' => 'date',
        'repeat_weekdays' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }
}