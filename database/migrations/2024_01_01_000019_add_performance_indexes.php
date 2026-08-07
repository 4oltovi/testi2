<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexes extends Migration
{
    /**
     * Индексҳои оптимизатсия барои суръати корӣ ба 500-800 донишҷӯ
     */
    public function up(): void
    {
        // Attendances: филтр аз рӯи донишҷӯ + фан + рӯз
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['student_id', 'subject_assignment_id', 'lesson_date'], 'attendance_student_subject_date_idx');
        });

        // SemesterGrades: ҳисоби GPA ва рейтинг
        Schema::table('semester_grades', function (Blueprint $table) {
            $table->index(['student_id', 'semester_id', 'is_finalized'], 'sg_student_semester_finalized_idx');
            $table->index(['curriculum_id', 'semester_id'], 'sg_curriculum_semester_idx');
        });

        // AcademicDebts: филтр аз рӯи ҳолат ва семестр
        Schema::table('academic_debts', function (Blueprint $table) {
            $table->index(['student_id', 'status', 'semester_id'], 'ad_student_status_semester_idx');
            $table->index(['debt_date', 'status'], 'ad_date_status_idx');
        });

        // CategoryScores: ҳисоби рейтинг аз рӯи категория
        Schema::table('category_scores', function (Blueprint $table) {
            $table->index(['student_id', 'subject_assignment_id', 'semester_id'], 'cs_student_assignment_semester_idx');
        });

        // CurrentGrades: рейтинги ҷорӣ
        Schema::table('current_grades', function (Blueprint $table) {
            $table->index(['student_id', 'subject_assignment_id', 'semester_id', 'week_number'], 'cg_student_assignment_semester_week_idx');
        });

        // ExamAttempts: кӯшишҳои имтиҳон
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->index(['exam_id', 'student_id', 'status'], 'ea_exam_student_status_idx');
        });

        // AuditLogs: ҷустуҷӯ аз рӯи корбар ва амал
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['user_id', 'action', 'created_at'], 'al_user_action_date_idx');
        });

        // SubjectAssignments: филтр аз рӯи омӯзгор ва семестр
        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->index(['teacher_id', 'semester_id', 'is_active'], 'sa_teacher_semester_active_idx');
            $table->index(['group_id', 'semester_id', 'is_active'], 'sa_group_semester_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendance_student_subject_date_idx');
        });
        Schema::table('semester_grades', function (Blueprint $table) {
            $table->dropIndex('sg_student_semester_finalized_idx');
            $table->dropIndex('sg_curriculum_semester_idx');
        });
        Schema::table('academic_debts', function (Blueprint $table) {
            $table->dropIndex('ad_student_status_semester_idx');
            $table->dropIndex('ad_date_status_idx');
        });
        Schema::table('category_scores', function (Blueprint $table) {
            $table->dropIndex('cs_student_assignment_semester_idx');
        });
        Schema::table('current_grades', function (Blueprint $table) {
            $table->dropIndex('cg_student_assignment_semester_week_idx');
        });
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex('ea_exam_student_status_idx');
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('al_user_action_date_idx');
        });
        Schema::table('subject_assignments', function (Blueprint $table) {
            $table->dropIndex('sa_teacher_semester_active_idx');
            $table->dropIndex('sa_group_semester_active_idx');
        });
    }
}