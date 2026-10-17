<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ContractPayment;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * «Рақами расид» дар формури пардохти муҳосиб:
 *  - лозим аст (required + attribute);
 *  - танҳо як майдони расид дар форма;
 *  - дубликат рад мешавад бо паёми тоҷикӣ;
 *  - пардохтҳои кӯҳна бе расид кор мекунанд («—»);
 *  - рақами расид дар рӯйхат нишон дода мешавад;
 *  - AuditLog сабт мешавад;
 *  - иҷозатҳо тағйир намеёбанд.
 */
class AccountantReceiptNumberTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;
    private User $studentUser;
    private Student $student;
    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $accountantRole = Role::firstOrCreate(
            ['name' => 'accountant'],
            ['display_name' => 'Муҳосиб', 'level' => 40, 'is_system' => true]
        );
        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            ['display_name' => 'Донишҷӯ', 'level' => 10, 'is_system' => true]
        );

        $this->accountant = User::factory()->create(['status' => 'active']);
        $this->accountant->roles()->attach($accountantRole->id);

        $this->year = AcademicYear::create([
            'name' => '2026-2027', 'start_year' => 2026, 'end_year' => 2027,
            'start_date' => '2026-09-01', 'end_date' => '2027-08-31', 'is_active' => true,
        ]);

        $faculty = Faculty::create(['name' => 'Факултеи Тест', 'code' => 'FAC-RC', 'is_active' => true]);
        $department = Department::create([
            'faculty_id' => $faculty->id, 'name' => 'Кафедраи Тест',
            'code' => 'DEP-RC', 'is_active' => true,
        ]);
        $specialty = Specialty::create([
            'department_id' => $department->id, 'faculty_id' => $faculty->id,
            'name' => 'Ихтисоси Тест', 'code' => 'SP-RC',
            'total_credits' => 120, 'is_active' => true,
        ]);
        $course = Course::create(['number' => 1, 'name' => 'Курси 1']);
        $group = Group::create([
            'specialty_id' => $specialty->id, 'course_id' => $course->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Гурӯҳи Тест', 'code' => '201', 'is_active' => true,
        ]);

        $this->studentUser = User::factory()->create(['status' => 'active']);
        $this->studentUser->roles()->attach($studentRole->id);

        $this->student = $this->studentUser->student()->create([
            'group_id' => $group->id,
            'specialty_id' => $specialty->id,
            'course_id' => $course->id,
            'student_id_number' => 'RC0001',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
            'education_form' => 'contract',
            'contract_amount' => 5000,
            'contract_paid' => 0,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'amount' => 1000,
            'payment_date' => '2026-10-01',
            'payment_method' => 'нақд',
            'receipt_number' => 'REC-001',
            'academic_year_id' => $this->year->id,
        ], $overrides);
    }

    public function test_receipt_number_is_required(): void
    {
        $this->actingAs($this->accountant)
            ->post(route('accountant.payments.store', $this->student), $this->validPayload([
                'receipt_number' => '',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('receipt_number');

        $this->assertDatabaseCount('contract_payments', 0);
    }

    public function test_receipt_number_missing_key_is_rejected(): void
    {
        $payload = $this->validPayload();
        unset($payload['receipt_number']);

        $this->actingAs($this->accountant)
            ->post(route('accountant.payments.store', $this->student), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('receipt_number');
    }

    public function test_required_message_is_in_tajik(): void
    {
        $this->actingAs($this->accountant)
            ->from(route('accountant.students.show', $this->student))
            ->post(route('accountant.payments.store', $this->student), $this->validPayload([
                'receipt_number' => '   ',
            ]))
            ->assertSessionHasErrors(['receipt_number' => 'Рақами расид лозим аст.']);
    }

    public function test_receipt_number_is_trimmed_before_save(): void
    {
        $this->actingAs($this->accountant)
            ->post(route('accountant.payments.store', $this->student), $this->validPayload([
                'receipt_number' => '  REC-TRIM  ',
            ]))
            ->assertRedirect();

        $this->assertSame('REC-TRIM', ContractPayment::first()->receipt_number);
    }

    public function test_duplicate_receipt_number_is_rejected_with_tajik_message(): void
    {
        // Як расид = як пардохт: барои ҳамон донишҷӯ ҳам дубликат рад мешавад
        $this->actingAs($this->accountant)
            ->post(route('accountant.payments.store', $this->student), $this->validPayload())
            ->assertRedirect();

        $this->assertSame(1, ContractPayment::count());

        $this->actingAs($this->accountant)
            ->from(route('accountant.students.show', $this->student))
            ->post(route('accountant.payments.store', $this->student), $this->validPayload([
                'amount' => 500,
            ]))
            ->assertSessionHasErrors('receipt_number');

        $errors = session('errors')->toArray();
        $this->assertStringContainsString('аллакай истифода шудааст', $errors['receipt_number'][0]);

        // Пардохти дуюм сабт нашуд
        $this->assertSame(1, ContractPayment::count());
    }

    public function test_legacy_payment_without_receipt_shows_dash_and_appears_in_list(): void
    {
        // Пардохти кӯҳна бе рақами расид
        ContractPayment::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'amount' => 700,
            'payment_date' => '2026-09-15',
            'payment_method' => 'нақд',
            'receipt_number' => null,
            'recorded_by' => $this->accountant->id,
        ]);

        $html = $this->actingAs($this->accountant)
            ->get(route('accountant.students.show', $this->student))
            ->assertOk()
            ->assertSee('700')
            ->getContent();

        // Расиди нодор «—» нишон дода мешавад
        $this->assertStringContainsString('—', $html);
        $this->assertStringNotContainsString('\u2014', $html);
    }

    public function test_receipt_number_shown_in_payment_list(): void
    {
        $this->actingAs($this->accountant)
            ->post(route('accountant.payments.store', $this->student), $this->validPayload([
                'receipt_number' => 'REC-VISIBLE-9',
            ]))
            ->assertRedirect();

        $this->actingAs($this->accountant)
            ->get(route('accountant.students.show', $this->student))
            ->assertOk()
            ->assertSee('REC-VISIBLE-9');

        // ... ва дар рӯйхати пардохтҳои охирин (дашборд)
        $this->actingAs($this->accountant)
            ->get(route('accountant.dashboard'))
            ->assertOk()
            ->assertSee('REC-VISIBLE-9');
    }

    public function test_form_contains_exactly_one_receipt_field(): void
    {
        $html = $this->actingAs($this->accountant)
            ->get(route('accountant.students.show', $this->student))
            ->assertOk()
            ->getContent();

        $matches = preg_match_all('/name="receipt_number"/', $html);
        $this->assertSame(1, $matches, 'Дар форма бояд танҳо як майдони «Рақами расид» бошад.');

        // Майдон бояд required бошад
        $this->assertMatchesRegularExpression('/name="receipt_number"[^>]*required|required[^>]*name="receipt_number"/s', $html);
    }

    public function test_audit_log_written_on_create(): void
    {
        $this->actingAs($this->accountant)
            ->post(route('accountant.payments.store', $this->student), $this->validPayload([
                'receipt_number' => 'REC-AUDIT-1',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->accountant->id,
            'action' => 'create',
            'model_type' => ContractPayment::class,
        ]);
    }

    public function test_receipt_number_max_length_enforced(): void
    {
        $this->actingAs($this->accountant)
            ->post(route('accountant.payments.store', $this->student), $this->validPayload([
                'receipt_number' => str_repeat('X', 51),
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('receipt_number');

        $this->assertDatabaseCount('contract_payments', 0);
    }

    public function test_student_cannot_access_accountant_routes(): void
    {
        // Иҷозатҳо тағйир намеёбанд: донишҷӯ ба кабинети муҳосиб дастрасӣ надорад
        $this->actingAs($this->studentUser)
            ->get(route('accountant.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->studentUser)
            ->post(route('accountant.payments.store', $this->student), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('contract_payments', 0);
    }
}
