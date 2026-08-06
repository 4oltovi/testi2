<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Истифодаи Bootstrap 5 барои пагинатсия
        Paginator::useBootstrapFive();

        // Кеш барои маълумоти статик дар ҳама саҳифаҳо
        view()->composer('*', function ($view) {
            view()->share('currentSemester', Cache::remember('current_semester', 3600, function () {
                return \App\Models\Semester::current();
            }));

            view()->share('activeAcademicYear', Cache::remember('active_academic_year', 3600, function () {
                return \App\Models\AcademicYear::where('is_active', true)->first();
            }));
        });
    }
}
