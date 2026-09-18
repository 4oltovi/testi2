## Objective
- Rewrite InitialDataSeeder, remove Institution concept from faculties, implement Teacher Excel Import, and remove teacher role from generic user-creation flow in Laravel "testi-main" project

## Important Details
- **InitialDataSeeder** successfully rewritten to reduced form (super admin + Course loop 1–5, 253→36 lines)
- **institution_id** fully removed from `faculties` table via migration `2026_09_16_000001_remove_institution_id_from_faculties.php`; Institution model/table kept as unused/orphaned per instructions
- **SQLite limitation**: Feature tests fail due to pre-existing `dropForeign` issue in `2026_09_11_143444_fix_semester_grades_foreign_key:14` — unrelated to any changes made
- **Department lookup** uses `Department::where('code', ...)` — `code` column confirmed unique in migration
- **TeacherActivityLog**: `teacher_id`, `activity_type`, `description`, `activity_date`, `created_by` fillable fields
- **Password auto-generation**: 10-char random password when column E blank; exposed in preview and results
- **Bug fixed**: `SemesterGrade::create()` in `Student\RatingController:265` and `AutoGradeExpiredExams:241` was missing `subject_id` (required non-nullable column in `semester_grades` table); added `'subject_id' => $subjectAssignment->subject_id` to both
- **Route names** use a mix of dots and hyphens (e.g., `excel-import`, `excel-import.template`, `excel-import-preview`, `excel-import-result`); views must match exactly
- **Teacher role excluded from admin/users**: Teacher role is not selectable in user create/edit forms; server-side rejection in store/update; teachers created exclusively via `admin.teachers` flow to ensure teachers table row is also created

## Work State
### Completed
- InitialDataSeeder rewritten to reduced form (super admin `admin`/`admin123456` + Courses 1–5 only; 18 `use` statements removed)
- Institution removal from faculties (migration `2026_09_16_000001_remove_institution_id_from_faculties.php`, Faculty model, FacultyController 6 methods, create/edit views, TestDataSeeder, 2 test files)
- Teacher Excel Import fully implemented:
  - `app/Services/TeacherExcelParser.php` — parse(), 20-column layout, validation per row, date parsing (Y-m-d, dd.mm.yyyy, Excel serial), employment type normalization (Доимӣ→full_time, Нимшата→part_time, Соатбайъ→hourly), gender normalization (Мард→male, Зан→female), password auto-generation
  - `app/Http/Controllers/Admin/TeacherExcelImportController.php` — importForm(), downloadTemplate(), upload(), preview(), confirm() (per-row DB transactions with AuditLog), result()
  - `routes/admin.php` — 6 routes inside teachers group (lines 71-76), all ordered before `/{teacher}` wildcard (line 78)
  - Views: `resources/views/admin/teachers/excel-import.blade.php`, `excel-import-preview.blade.php`, `excel-import-result.blade.php`
  - `resources/views/admin/teachers/index.blade.php` — "Импорт аз Excel" button added at line 11
- **Bug fixed**: 3 route name mismatches in views corrected to match route definitions:
  - `excel-import.blade.php:12` — `excel-import-template` → `excel-import.template`
  - `excel-import.blade.php:17` — `excel-import-upload` → `excel-import.upload`
  - `excel-import-preview.blade.php:111` — `excel-import-confirm` → `excel-import.confirm`
- **Teacher role removed from admin/users UI**:
  - `UserController::create()` (line 54): `Role::where('name', '!=', 'teacher')` — teacher excluded from role checkboxes on create form
  - `UserController::edit()` (line 134): Same exclusion on edit form
  - `UserController::store()` (lines 92-98): Server-side rejection — if teacher role submitted, redirects back with Tajik error: "Барои сохтани омӯзгор аз саҳифаи «Омӯзгорон» истифода баред..."
  - `UserController::update()` (lines 164-170): Same server-side rejection with Tajik error: "Барои таҳрири профили омӯзгор аз саҳифаи «Омӯзгорон» истифода баред."
  - `UserController::index()` (line 44): Teacher kept in filter dropdown (for searching existing users) — only creation forms exclude it
  - Explanatory comments added in store() and update() explaining why teacher role must not be assigned via this flow
- Route cache cleared and rebuilt; all 6 import routes verified to resolve correctly
- All 14 unit tests pass; all PHP syntax checks pass

### Active
- No active incomplete work — all tasks completed and verified

### Blocked
- Nothing blocked

## Next Move
1. No further actions required — all work is complete and verified

## Relevant Files
- `database/seeders/InitialDataSeeder.php` — rewritten to reduced form (verified: 1 super admin, 5 courses, all other tables empty)
- `database/migrations/2026_09_16_000001_remove_institution_id_from_faculties.php` — new migration, drops FK/index/column; down() re-adds as nullable
- `app/Models/Faculty.php` — removed `institution()` BelongsTo, `institution_id` from `$fillable`, `BelongsTo` import
- `app/Http/Controllers/Admin/FacultyController.php` — all Institution references removed from index/create/store/show/edit/update
- `app/Http/Controllers/Admin/UserController.php` — teacher excluded from create/edit roles; server-side rejection in store/update with Tajik error messages and explanatory comments
- `app/Http/Controllers/Admin/TeacherExcelImportController.php` — new file (full import flow)
- `app/Services/TeacherExcelParser.php` — new file (parsing + validation)
- `routes/admin.php` — teachers routes at lines 66-81, import routes at lines 71-76
- `resources/views/admin/structure/faculties/create.blade.php` — hidden institution_id input removed
- `resources/views/admin/structure/faculties/edit.blade.php` — institution select block removed
- `database/seeders/TestDataSeeder.php` — Institution creation and institution_id removed from faculty
- `tests/Feature/QuestionExcelImportTest.php` — Institution usage removed from createSubject()
- `tests/Feature/RatingQuestionImportTest.php` — Institution usage removed from createSubject()
- `resources/views/admin/teachers/index.blade.php` — import button added at line 11
- `resources/views/admin/teachers/excel-import.blade.php` — upload form view (route names fixed)
- `resources/views/admin/teachers/excel-import-preview.blade.php` — preview table view (route name fixed)
- `resources/views/admin/teachers/excel-import-result.blade.php` — results view with passwords
