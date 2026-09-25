## Objective
- Rewrite InitialDataSeeder, remove Institution concept from faculties, implement Teacher Excel Import, and remove teacher role from generic user-creation flow in Laravel "testi-main" project

## Important Details
- **InitialDataSeeder** successfully rewritten to reduced form (super admin `admin`/`admin123456` + Courses 1–5, 253→36 lines)
- **institution_id** fully removed from `faculties` table via migration `2026_09_16_000001_remove_institution_id_from_faculties.php`; Institution model/table kept as unused/orphaned per instructions
- **SQLite limitation**: Feature tests fail due to pre-existing `dropForeign` issue in `2026_09_11_143444_fix_semester_grades_foreign_key:14` — unrelated to any changes made
- **Department lookup** uses `Department::where('code', ...)` — `code` column confirmed unique in migration
- **TeacherActivityLog**: `teacher_id`, `activity_type`, `description`, `activity_date`, `created_by` fillable fields
- **Password auto-generation**: 10-char random password when column E blank; exposed in preview and results
- **Bug fixed**: `SemesterGrade::create()` in `Student\RatingController:265` and `AutoGradeExpiredExams:241` was missing `subject_id` (required non-nullable column in `semester_grades` table); added `'subject_id' => $subjectAssignment->subject_id` to both
- **Bug fixed**: `RetakeExamController::checkMainExam()` (line 213) and `store()` (line 106) used unordered `->limit(1)` subquery on `subject_assignments` — when a subject has multiple groups, it picked arbitrary subject_assignment, falsely reporting "no main exam found". Fixed: replaced with `whereIn` against ALL matching subject_assignments; added per-group main-exam status to `checkMainExam` JSON response; added group coverage warning to `store` success message
- **Route names** use a mix of dots and hyphens (e.g., `excel-import`, `excel-import.template`, `excel-import-preview`, `excel-import-result`); views must match exactly
- **Teacher role excluded from admin/users**: Teacher role is not selectable in user create/edit forms; server-side rejection in store/update; teachers created exclusively via `admin.teachers` flow to ensure teachers table row is also created
- **Specialty→Faculty relationship chain restructured**: Specialty now has `faculty_id` directly (`faculty()` BelongsTo); `department()` kept as `@deprecated` for rollback safety; Faculty's `specialties()` changed from `HasManyThrough` to `HasMany`; `ResolvesDeanFaculty::extractFacultyId()` uses `faculty?->id` for Specialty/Group; all controllers simplified from `department.faculty` to `faculty` for Specialty loads
- **Attendance bug (resolved)**: `AttendanceReportController::index()` queries correctly use `$startDate`/`$endDate` from request params (defaults: 7 days ago → today); data confirmed present in `daily_attendance` (10 rows for 2026-09-16); `attendances` Eloquent table is orphaned (0 rows) but not used by report queries
- **Hard-delete migration (2026_09_21)**: Removed `deleted_at` column from `faculties`, `departments`, `specialties`, `groups`, `subjects`; removed `SoftDeletes` trait from corresponding 5 models; force-deleted 3 stale Faculty rows; added `teachers()` guard to DepartmentController::destroy(); added `subjectAssignments()` guard to GroupController::destroy
- **FLAG**: `academic_debts.subject_id` has `cascadeOnDelete` — hard-deleting a Subject cascade-deletes AcademicDebt records (pre-existing FK behavior)
- **Models kept as soft-delete**: Teacher, Exam, Question, RetakeExam, RetakeExamStudent, StudentTransfer, AcademicDebt

## Work State
### Completed
- InitialDataSeeder rewritten to reduced form (super admin `admin`/`admin123456` + Courses 1–5 only; 18 `use` statements removed)
- Institution removal from faculties (migration, Faculty model, FacultyController 6 methods, create/edit views, TestDataSeeder, 2 test files)
- Specialty→Faculty relationship restructuring (completed):
  - Migration `2026_09_19_000001_add_faculty_id_to_specialties.php` (anonymous class), backfill verified
  - `Specialty.php` model: `faculty_id` in fillable, `faculty(): BelongsTo`, `department()` marked `@deprecated`
  - `Faculty.php` model: `specialties()` changed from `HasManyThrough` to `HasMany`, `getStudentsCountAttribute()` simplified
  - All controllers updated: `department.faculty` → `faculty` for Specialty eager loads (Management/SpecialtyController, Admin/GroupController, Admin/StudentController, Admin/AttendanceReportController)
  - `ResolvesDeanFaculty` trait updated for new chain
- Teacher Excel Import fully implemented:
  - `app/Services/TeacherExcelParser.php` — parse(), 20-column layout, validation per row, date parsing, employment/gender normalization, password auto-generation
  - `app/Http/Controllers/Admin/TeacherExcelImportController.php` — full import flow (form, template download, upload, preview, confirm, result)
  - `routes/admin.php` — 6 routes ordered before `/{teacher}` wildcard
  - Views: `resources/views/admin/teachers/excel-import.blade.php`, `excel-import-preview.blade.php`, `excel-import-result.blade.php`
  - `resources/views/admin/teachers/index.blade.php` — import button added
- **Bug fixed**: 3 route name mismatches in views corrected to match route definitions
- **Teacher role removed from admin/users**:
  - `UserController::create()`/`edit()`: teacher excluded from role checkboxes
  - `UserController::store()`/`update()`: server-side rejection with Tajik error messages
  - `UserController::index()`: Teacher kept in filter dropdown (for searching existing users)
- **AttendanceReportController verified**: All 4 data queries use `$startDate`/`$endDate` from request params; data confirmed present in `daily_attendance`
- **Admin\SpecialtyController fixed**: `departments` variable now passed to all 3 views (index, create, edit); `department_id` filter added to index query; `department` eager-loaded with `faculty` sub-load; store/update now accept `department_id` from form and auto-resolve `faculty_id` from it
- **Hard-delete migration (2026_09_21)** — 5 models switched from soft-delete to hard-delete:
  - Models: Faculty, Department, Specialty, Group, Subject
  - Migrations: `2026_09_21_000001` through `2026_09_21_000005` — drop `deleted_at` column
  - `SoftDeletes` trait removed from all 5 model files
  - 3 stale Faculty rows force-deleted (verified: 0 remaining)
  - Guard gap fixes: DepartmentController adds `teachers()` check; GroupController adds `subjectAssignments()` check
  - DB verified: none of 5 tables have `deleted_at` column
  - Models kept soft-delete: Teacher, Exam, Question, RetakeExam, RetakeExamStudent, StudentTransfer, AcademicDebt
- Admin\SpecialtyController fixed: `departments` variable passed to all 3 views (index, create, edit); `department_id` filter added to index query; `department` eager-loaded with `faculty` sub-load; store/update validate `department_id` and auto-resolve `faculty_id`
- Route cache rebuilt and verified; all 14 unit tests pass; all PHP syntax checks pass

### Active
- No active incomplete work — all tasks completed and verified

### Blocked
- Nothing blocked

## Next Move
1. Task 2 (Simplify per-group scheduling UX for rating sessions) — pending user confirmation to proceed

## Relevant Files
- `database/seeders/InitialDataSeeder.php` — rewritten to reduced form
- `database/migrations/2026_09_16_000001_remove_institution_id_from_faculties.php` — drops FK/index/column
- `database/migrations/2026_09_19_000001_add_faculty_id_to_specialties.php` — adds faculty_id, backfills from department
- `app/Models/Faculty.php` — removed institution; `specialties()` = HasMany (was HasManyThrough)
- `app/Models/Specialty.php` — `faculty_id` fillable, `faculty()` BelongsTo, `department()` @deprecated
- `app/Models/Department.php` — unchanged (still has faculty_id)
- `app/Http/Controllers/Admin/FacultyController.php` — Institution references removed
- `app/Http/Controllers/Admin/UserController.php` — teacher excluded from create/edit roles; server-side rejection
- `app/Http/Controllers/Admin/TeacherExcelImportController.php` — new file
- `app/Http/Controllers/Admin/GroupController.php` — Specialty eager load simplified
- `app/Http/Controllers/Admin/StudentController.php` — Specialty eager load simplified
- `app/Http/Controllers/Admin/AttendanceReportController.php` — Specialty eager load simplified
- `app/Http/Controllers/Management/SpecialtyController.php` — Specialty eager load simplified
- `app/Traits/ResolvesDeanFaculty.php` — extractFacultyId updated for new chain
- `app/Services/TeacherExcelParser.php` — new file
- `routes/admin.php` — teachers routes + import routes
- `resources/views/admin/teachers/` — 3 blade files
- `resources/views/admin/structure/faculties/create.blade.php` — institution input removed
- `resources/views/admin/structure/faculties/edit.blade.php` — institution select removed
- `database/seeders/TestDataSeeder.php` — Institution/institution_id removed
