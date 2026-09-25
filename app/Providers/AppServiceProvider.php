<?php

namespace App\Providers;

use App\Models\RatingAttempt;
use App\Models\SemesterGrade;
use App\Observers\RatingAttemptObserver;
use App\Observers\SemesterGradeObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Ҳангоми ворид шудан — токени ягонаи сессия сохта мешавад
        Event::listen(Login::class, function (Login $event) {
            $token = Str::random(40);
            $event->user->forceFill(['session_token' => $token])->save();
            session()->put('single_session_token', $token);
        });

        // Custom pagination view with smaller arrows
        Paginator::defaultView('vendor.pagination.bootstrap-5');

        // НАВ: Observer барои rating_attempts → semester_grades
        RatingAttempt::observe(RatingAttemptObserver::class);
        SemesterGrade::observe(SemesterGradeObserver::class);
    }
}
