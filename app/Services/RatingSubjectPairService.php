<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\SubjectAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Қоидаи «гурӯҳ фанро мехӯҷад» барои рейтингҳо.
 *
 * Сарчашмаи истифода — `subject_assignments`: ин таъиноти журнал аст
 * (фан + гурӯҳ + семестр + омӯзгор). Ҳамаи сабтҳои журнал
 * (`attendances`, `current_grades`, `category_scores`, `semester_grades`)
 * ба `subject_assignment_id` такя мекунанд, бинобар ин маълумоти
 * «фан ба кадом гурӯҳ дар кадом семестр таъсиф шудааст» аз ҳуқуқи
 * байни фан ва гурӯҳ меояд — на аз барои хатм шудани баҳоҳо.
 * Ҳамин тавр, барои фан дар семестр барои гурӯҳ ҳамасқ аҳолӣ
 * лозим нест.
 *
 * ── Сатрҳои обудашуда (`merged_into_id`) ───────────────────────────────
 * `assignments:merge-duplicates` сатрҳои якхелаи
 * (фан + омӯзгор + гурӯҳ + семестр)-ро як сатр мекунонад: сатри канон
 * мемонад, ҳарои онҳо `merged_into_id` гузошта мешавад ва `is_active=false`
 * мегарданд. Сабаби `is_active=false` танҳо обуда аст, на аз ҳазфи
 * таъинот аз семестр. Бинобар ин:
 *
 *  - сатри обудашуда ҳАМЕШан ҳисоб мешавад: гурӯҳи он фанро дар семестр
 *    мехӯҷад;
 *  - фани он аз рӯи ҳадафи обуда (`merged_into_id` → сатри канон) даст
 *    мешавад — то ҳолатҳое, ки як лексия ба якчанд гурӯҳ муштарак аст,
 *    ҳам гурӯҳ дуртар ҳам фан дуруст истодад;
 *  - гурӯҳ аз рӯи сатри худи худ (гурӯҳ иваз намешавад).
 *
 * Натиҷа: як гурӯҳ фақат он фанҳоро мебинад, ки воқеан дар он
 * семестр мехӯҷад; фанҳои дигар гурӯҳ ба ӯ сарфи намешавад.
 */
class RatingSubjectPairService
{
    /**
     * Сатрҳои таъинот, ки ҳадафи қоидаро таъмин мекунанд.
     *
     * Ҳар сатр дар натиҷа:
     *   group_id   — гурӯҳи сатр (иваз намешавад);
     *   subject_id — фани ҳадаф (барои сатри обудашуда — фани сатри канон).
     *
     * @param  array<int>  $subjectIds  филтр бо рӯи фан (холӣ = ҳамаи фанҳо)
     * @param  array<int>  $groupIds    филтр бо рӯи гурӯҳ (холӣ = ҳамаи гурӯҳҳо)
     * @return Collection<int, object>
     */
    private function assignmentRows(?int $semesterId, array $subjectIds = [], array $groupIds = []): Collection
    {
        if (! $semesterId) {
            return collect();
        }

        $subjectIds = array_values(array_unique(array_map('intval', $subjectIds)));
        $groupIds   = array_values(array_unique(array_map('intval', $groupIds)));

        $table = (new SubjectAssignment)->getTable();

        // Фани ҳадаф: барои сатри обудашуда — сатри канон, барои дигар — худи сатр
        $resolvedSubject = 'COALESCE(target.subject_id, sa.subject_id)';

        return DB::table("{$table} as sa")
            ->leftJoin("{$table} as target", 'sa.merged_into_id', '=', 'target.id')
            ->where('sa.semester_id', $semesterId)
            ->where(function ($q) {
                // Таъиноти обуданашуда: `is_active=false` танҳо аз сабаби
                // обуда аст → ҳамошанабизор аст. Ҳадаф ёфта шавад (дараҳо).
                $q->where(function ($q) {
                    $q->whereNull('sa.merged_into_id')->where('sa.is_active', true);
                })->orWhere(function ($q) {
                    $q->whereNotNull('sa.merged_into_id');
                });
            })
            ->when($subjectIds !== [], fn ($q) => $q->whereIn(DB::raw($resolvedSubject), $subjectIds))
            ->when($groupIds !== [], fn ($q) => $q->whereIn('sa.group_id', $groupIds))
            ->select([
                'sa.group_id',
                DB::raw($resolvedSubject . ' as subject_id'),
            ])
            ->get();
    }

    /**
     * Ҷуфтҳои (фан, гурӯҳ)-и фаъол дар як семестр.
     *
     * @param  array<int>  $subjectIds  филтр бо рӯи фан (холӣ = ҳамаи фанҳо)
     * @param  array<int>  $groupIds    филтр бо рӯи гурӯҳ (холӣ = ҳамаи гурӯҳҳо)
     * @return array<int, array{subject_id:int, group_id:int}>
     */
    public function pairs(?int $semesterId, array $subjectIds = [], array $groupIds = []): array
    {
        $pairs = [];

        foreach ($this->assignmentRows($semesterId, $subjectIds, $groupIds) as $row) {
            $subjectId = (int) $row->subject_id;
            $groupId   = (int) $row->group_id;

            $pairs[$subjectId . ':' . $groupId] = [
                'subject_id' => $subjectId,
                'group_id'   => $groupId,
            ];
        }

        return array_values($pairs);
    }

    /**
     * Шумораи гурӯҳҳое, ки ин фанро дар семестр мехӯҷанд.
     *
     * @param  array<int>  $subjectIds
     * @return array<int, int>  [subject_id => шумораи гурӯҳҳо]
     */
    public function groupCounts(?int $semesterId, array $subjectIds = []): array
    {
        $counts = [];

        foreach ($this->pairs($semesterId, $subjectIds) as $pair) {
            $counts[$pair['subject_id']] = ($counts[$pair['subject_id']] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * ID-и фанҳое, ки гурӯҳ дар семестр мехӯҷад.
     *
     * @return array<int>
     */
    public function subjectIdsForGroup(?int $semesterId, ?int $groupId): array
    {
        if (! $groupId) {
            return [];
        }

        $ids = [];

        foreach ($this->pairs($semesterId, [], [$groupId]) as $pair) {
            $ids[$pair['subject_id']] = $pair['subject_id'];
        }

        return array_values($ids);
    }

    /**
     * Оё гурӯҳ ин фанро дар семестр мехӯҷад?
     *
     * Як ҳолати обудашуда ҳамро: гурӯҳ фанро аз рӯи ҳадафи обуда
     * мехӯҷад (ҳамон ҳолат, ки дар `pairs()` ҳисоб мешавад).
     */
    public function groupStudiesSubject(?int $semesterId, ?int $groupId, ?int $subjectId): bool
    {
        if (! $semesterId || ! $groupId || ! $subjectId) {
            return false;
        }

        return $this->assignmentRows($semesterId, [$subjectId], [$groupId])->isNotEmpty();
    }

    /**
     * Ҳамаи фанҳои фаъол бо шумораи гурӯҳҳое, ки онҳоро дар семестр мехӯҷанд.
     *
     * Атрибути `groups_count` ба ҳар фан гузошта мешавад (0 = «Гурӯҳ нест»).
     *
     * @param  array<int>  $groupIds
     * @return Collection<int, Subject>
     */
    public function subjectsWithGroupCounts(?int $semesterId, array $groupIds = []): Collection
    {
        $counts = [];

        foreach ($this->pairs($semesterId, [], $groupIds) as $pair) {
            $counts[$pair['subject_id']] = ($counts[$pair['subject_id']] ?? 0) + 1;
        }

        return Subject::active()
            ->orderBy('name')
            ->get()
            ->each(function (Subject $subject) use ($counts) {
                $subject->setAttribute('groups_count', (int) ($counts[$subject->id] ?? 0));
            });
    }
}
