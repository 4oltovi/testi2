<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\RatingAttempt;
use App\Models\RatingSession;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RatingController extends Controller
{
    private function student(): ?Student
    {
        return auth()->user()->student ?? null;
    }

    /**
     * Рӯйхати рейтингҳои ман
     */
    public function index(): View
    {
        $student = $this->student();

        $session = null;
        $attemptsMap = collect();

        if ($student) {
            // Сессияи фаъол ва кушод барои гурӯҳи донишҷӯ (бе филтри семестр)
            $sessionId = Cache::remember(
                'open_rating_session:' . ($student->group_id ?? 0),
                60,
                fn() => RatingSession::where('status', 'active')
                    ->orderByDesc('id')
                    ->get()
                    ->first(fn($s) => $s->isOpenForGroup($student->group_id))?->id
            );

            if ($sessionId) {
                $session = RatingSession::with('subjects')->find($sessionId);

                $attemptsMap = RatingAttempt::where('rating_session_id', $sessionId)
                    ->where('student_id', $student->id)
                    ->get()
                    ->groupBy('subject_id');
            }
        }

        return view('student.rating.index', compact('student', 'session', 'attemptsMap'));
    }

    /**
     * Оғоз кардани тест
     */
    public function start(RatingSession $ratingSession, \App\Models\Subject $subject): RedirectResponse
    {
        $student = $this->student();
        abort_unless($student, 403);
        abort_unless($ratingSession->status === 'active', 403);
        abort_unless($ratingSession->isOpenForGroup($student->group_id), 403);
        abort_unless($ratingSession->subjects->contains('id', $subject->id), 404);

        $attempts = RatingAttempt::where('rating_session_id', $ratingSession->id)
            ->where('student_id', $student->id)
            ->where('subject_id', $subject->id)
            ->get();

        // Кӯшишҳои кушодаи кӯҳна (вақт гузашта) → auto_closed
        foreach ($attempts->where('status', 'in_progress') as $old) {
            if ($old->started_at && now()->greaterThan($old->started_at->addMinutes($ratingSession->duration_minutes + 5))) {
                $old->update(['status' => 'auto_closed']);
            }
        }

        // Агар кӯшиши кушодаи фаъол ҳаст — идома
        $open = $attempts->firstWhere('status', 'in_progress');
        if (
            $open && $open->started_at
            && now()->lessThanOrEqualTo($open->started_at->addMinutes($ratingSession->duration_minutes + 5))
        ) {
            return redirect()->route('student.rating.take', $open);
        }

        // Лимити кӯшишҳо (админ мегузорад)
        $used = $attempts->whereIn('status', ['finished', 'in_progress'])->count();
        if ($used >= $ratingSession->max_attempts) {
            return back()->with('error', 'Лимити кӯшишҳои шумо тамом шуд.');
        }

        // Интихоби саволҳо — суръатнок: ID → shuffle дар PHP → fetch
        $ids = DB::table('questions as q')
            ->join('question_banks as qb', 'qb.id', '=', 'q.question_bank_id')
            ->where('qb.bank_type', 'rating')
            ->where('qb.subject_id', $subject->id)
            ->where('q.is_active', true)
            ->pluck('q.id');

        if ($ids->count() < $ratingSession->questions_count) {
            return back()->with('error', 'Барои ин фан саволҳо кофӣ нестанд.');
        }

        $chosen = $ids->shuffle()->take($ratingSession->questions_count)->values();

        $attempt = RatingAttempt::create([
            'rating_session_id' => $ratingSession->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'attempt_number' => $attempts->count() + 1,
            'started_at' => now(),
            'status' => 'in_progress',
            'answers_json' => ['ids' => $chosen->all(), 'answers' => []],
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('student.rating.take', $attempt);
    }

    /**
     * Саҳифаи тест (бо таймер)
     */
    public function take(RatingAttempt $ratingAttempt)
    {
        $student = $this->student();
        abort_unless($student && $ratingAttempt->student_id === $student->id, 403);

        if ($ratingAttempt->status !== 'in_progress') {
            return redirect()->route('student.rating.index');
        }

        $session = $ratingAttempt->session;
        $deadline = $ratingAttempt->started_at->addMinutes($session->duration_minutes);

        // Вақт тамом шуда бошад → автоматӣ ба супоридан
        if (now()->greaterThan($deadline->addMinute())) {
            return redirect()->route('student.rating.submit', $ratingAttempt);
        }

        $ids = $ratingAttempt->answers_json['ids'] ?? [];

        $questions = Question::whereIn('id', $ids)
            ->with('answerOptions')
            ->get()
            ->shuffle();

        // Вариантҳо ҳам омехта мешаванд (дар PHP — бе query иловагӣ)
        foreach ($questions as $q) {
            $q->setRelation('answerOptions', $q->answerOptions->shuffle());
        }

        return view('student.rating.take', [
            'attempt' => $ratingAttempt,
            'session' => $session,
            'questions' => $questions,
            'endsAt' => $deadline->timestamp,
        ]);
    }

    /**
     * Супоридан + ҳисоби автоматӣ
     */
    public function submit(Request $request, RatingAttempt $ratingAttempt): RedirectResponse
    {
        $student = $this->student();
        abort_unless($student && $ratingAttempt->student_id === $student->id, 403);
        abort_unless($ratingAttempt->status === 'in_progress', 403);

        $answers = $request->input('answers', []);
        $ids = $ratingAttempt->answers_json['ids'] ?? [];

        // Ҷавобҳои дуруст — ЯК query
        $correctOptions = AnswerOption::whereIn('question_id', $ids)
            ->where('is_correct', true)
            ->pluck('id', 'question_id');

        $correct = 0;
        $given = [];
        foreach ($ids as $qid) {
            $opt = (int) ($answers[$qid] ?? 0);
            $given[$qid] = $opt ?: null;

            if ($opt && $opt === (int) ($correctOptions[$qid] ?? 0)) {
                $correct++;
            }
        }

        $total = count($ids) ?: 1;
        $percentage = round(($correct / $total) * 100, 2);

        $ratingAttempt->update([
            'answers_json' => ['ids' => $ids, 'answers' => $given],
            'correct_count' => $correct,
            'total_questions' => $total,
            'percentage' => $percentage,
            'status' => 'finished',
            'finished_at' => now(),
        ]);

        return redirect()->route('student.rating.index')
            ->with('success', "✅ Рейтинг супорида шуд: {$percentage}%");
    }
}
