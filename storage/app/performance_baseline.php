<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$user = App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super_admin']))->first()
    ?? App\Models\User::first();

$benchmarks = [
    'admin.dashboard' => fn () => app(App\Http\Controllers\Admin\DashboardController::class)->index(requestFor('/admin/dashboard', $user)),
    'admin.reports.index' => fn () => app(App\Http\Controllers\Admin\ReportController::class)->index(),
    'admin.students.index' => fn () => app(App\Http\Controllers\Admin\StudentController::class)->index(requestFor('/admin/students', $user)),
    'admin.journal.index' => fn () => app(App\Http\Controllers\Admin\JournalController::class)->index(requestFor('/admin/journal', $user)),
    'admin.questions.index' => fn () => app(App\Http\Controllers\Admin\QuestionController::class)->index(requestFor('/admin/exams/questions', $user)),
    'admin.reports.students' => fn () => app(App\Http\Controllers\Admin\ReportController::class)->students(requestFor('/admin/reports/students', $user)),
    'admin.reports.debtors' => fn () => app(App\Http\Controllers\Admin\ReportController::class)->debtors(requestFor('/admin/reports/debtors', $user)),
];

$queries = [];
DB::listen(function ($query) use (&$queries) {
        $sql = preg_replace('/\s+/', ' ', $query->sql);
        $queries[] = ['sql' => $sql, 'time' => (float) $query->time];
});

foreach ($benchmarks as $name => $callback) {
    $queries = [];

    $start = hrtime(true);
    $memoryStart = memory_get_usage(true);
    $error = null;
    try {
        $callback();
    } catch (Throwable $e) {
        $error = get_class($e) . ': ' . $e->getMessage();
    }
    $elapsed = (hrtime(true) - $start) / 1e6;
    $dbTime = array_sum(array_column($queries, 'time'));
    $counts = array_count_values(array_column($queries, 'sql'));
    $duplicates = count(array_filter($counts, fn ($count) => $count > 1));
    usort($queries, fn ($a, $b) => $b['time'] <=> $a['time']);

    echo json_encode([
        'page' => $name,
        'response_ms' => round($elapsed, 2),
        'queries' => count($queries),
        'db_ms' => round($dbTime, 2),
        'memory_mb_delta' => round((memory_get_peak_usage(true) - $memoryStart) / 1048576, 2),
        'slowest_query_ms' => round($queries[0]['time'] ?? 0, 2),
        'duplicate_query_shapes' => $duplicates,
        'n_plus_one_candidate' => count($queries) > 20 && $duplicates > 2,
        'slowest_query' => $queries[0]['sql'] ?? null,
        'error' => $error,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

function requestFor(string $uri, ?object $user): Request
{
    $request = Request::create($uri, 'GET');
    $request->setUserResolver(fn () => $user);
    return $request;
}
