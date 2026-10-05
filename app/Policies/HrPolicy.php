<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;

/**
 * Қоидаҳои нақши «Кадр».
 *
 * Танзими донишҷӯён: Кадр танҳо барои тасдиқи шахсияти донишҷӯ (маълумотнома)
 * донишҷӯёнро мебинад. Ҳеҷ чи корманд, ҳеҷ баҳо, ҳеҷ қарздорӣ, ҳеҷ моликия.
 * Бар хато — барои диҳи 403 аз роҳҳо, на танҳо пинҳон кардани тугмаҳо дар UI.
 *
 * Кадр танҳо кормандро идора мекунад: корбарони корӣ, ки суперадмин, админ ва
 * донишҷӯ НЕ ҳастанд.
 */
class HrPolicy
{
    // =========================================================
    // Корманд (employees)
    // =========================================================

    /**
     * Оё корбар метавонад умуман саҳифаи кормандро бинӯрд?
     */
    public function manageAny(User $user): bool
    {
        return $user->isHr();
    }

    /**
     * Оё корбар метавонад ягон кормандро бинӯрад?
     *
     * Дар пешниёди корманд: админ/суперадмин ӯ надоранд, чунки Кадр супориши
     * идоракунии онҳоро надорад. Дар non-employee ҳам ӯ надоранд, чунки ин
     * рӯйхат танҳо корманд аст.
     */
    public function manageView(User $user, User $target): bool
    {
        return $user->isHr() && $this->isEmployee($target);
    }

    /**
     * Оё Кадр метавонад кормандро соҳтад?
     */
    public function manageCreate(User $user): bool
    {
        return $user->isHr();
    }

    /**
     * Оё Кадр метавонад ин кормандро таҳрир кунад?
     */
    public function manageUpdate(User $user, User $target): bool
    {
        return $user->isHr() && $this->isEmployee($target);
    }

    /**
     * Оё Кадр метавонад нақши кормандро иваз кунад?
     *
     * Ҳеҷ ҳолат дар ҳамон нақш: Кадр нақши худро иваз карда наметавонад, то ки
     * худ дар гум шуда нашавад (ва ба худ дастёби идоракунии худ нагуярад).
     */
    public function manageChangeRoles(User $user, User $target): bool
    {
        return $user->isHr()
            && $this->isEmployee($target)
            && ! $this->isSelf($user, $target);
    }

    /**
     * Оё Кадр метавонад кормандро фаъол/ғайриф аъол кунад?
     *
     * Худро ҳам: ҳеҷ корбар набояд худро ғайрифаъол кунад.
     */
    public function manageToggleStatus(User $user, User $target): bool
    {
        return $user->isHr()
            && $this->isEmployee($target)
            && ! $this->isSelf($user, $target);
    }

    /**
     * Нест кардани корманд — Кадр ҳаққи нест кардан надорад (фаъолсозӣ бехатартар).
     */
    public function manageDelete(User $user, User $target): bool
    {
        return false;
    }

    // =========================================================
    // Донишҷӯён — танҳо хондан, танҳо барои тасдиқи шахсият
    // =========================================================

    /**
     * Оё Кадр метавонад рӯйхати донишҷӯёнро бинӯрад?
     */
    public function viewAnyStudent(User $user): bool
    {
        return $user->isHr();
    }

    /**
     * Оё Кадр метавонад ин донишҷӯро бинӯрад (саҳифаи identity)?
     */
    public function viewStudent(User $user, Student $student): bool
    {
        return $user->isHr();
    }

    /**
     * Ҳамаи таҳрирҳо — маҷбурӣ 403. Ягора роҳи дуруст ин аст, ки кадр-и донишҷӯро
     * дар саҳифаи корманд афзудан мумкин аст, дар донишҷӯён не.
     */
    public function createStudent(User $user): bool
    {
        return false;
    }

    public function updateStudent(User $user, Student $student): bool
    {
        return false;
    }

    public function deleteStudent(User $user, Student $student): bool
    {
        return false;
    }

    public function changeStudentStatus(User $user, Student $student): bool
    {
        return false;
    }

    public function transferStudent(User $user, Student $student): bool
    {
        return false;
    }

    /**
     * Нест кардани сурати донишҷӯ — танҳо `students.edit` / суперадмин.
     */
    public function removeStudentAvatar(User $user, Student $student): bool
    {
        return false;
    }

    // =========================================================
    // Ёргашҳо
    // =========================================================

    /**
     * Оё корбар «корманд» аст — яъне корбари корӣ?
     *
     * Админ, суперадмин ва донишҷӯ корманд НЕ ҳастанд ва дар рӯйхати Кадр
     * намеоянд.
     */
    public function isEmployee(User $target): bool
    {
        $roleNames = $target->roles->pluck('name');

        if ($roleNames->isEmpty()) {
            return false;
        }

        $forbidden = [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::STUDENT->value,
        ];

        // Агар ягон нақши маҳдуд дошта бошад — корманд нест (ҳатто агар
        // дар як вақт нақши корӳ ҳам дошт бошад).
        if ($roleNames->intersect($forbidden)->isNotEmpty()) {
            return false;
        }

        return $roleNames->intersect(UserRole::employeeRoles())->isNotEmpty();
    }

    /**
     * Оё ин корбари мо аст?
     */
    public function isSelf(User $actor, User $target): bool
    {
        return $actor->getKey() === $target->getKey();
    }
}