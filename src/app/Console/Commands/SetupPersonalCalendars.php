<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Calendar;
use App\Models\Event;

class SetupPersonalCalendars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-personal-calendars';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create personal calendars for existing users and attach existing events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            $calendar = Calendar::firstOrCreate(
                [
                    'owner_user_id' => $user->id,
                    'name' => 'マイカレンダー',
                ],
                [
                    'color' => 'blue',
                ]
            );

            $calendar->users()->syncWithoutDetaching([
                $user->id => ['role' => 'owner']
            ]);

            Event::where('user_id', $user->id)
                ->whereNull('calendar_id')
                ->update([
                    'calendar_id' => $calendar->id,
                ]);

            $this->info("User {$user->id}: マイカレンダー作成/紐づけ完了");
        }

        $this->info('すべての既存ユーザーにマイカレンダーを設定しました。');

        return Command::SUCCESS;
    }
}