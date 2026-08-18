<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Role;
use App\Models\User;
use App\Models\Subject;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Institution;
use App\Models\QuestionBank;
use App\Models\Question;
use App\Models\AnswerOption;
use Illuminate\Http\UploadedFile;

class RatingQuestionImportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $role = Role::where('name', 'admin')->firstOrCreate([
            'name' => 'admin',
            'display_name' => 'Администратор',
            'level' => 90,
            'is_system' => true,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function createSubject(string $name, string $code): Subject
    {
        $institution = Institution::create([
            'name' => 'Муассисаи ' . $name,
            'is_active' => true,
        ]);

        $faculty = Faculty::create([
            'institution_id' => $institution->id,
            'name' => 'Факултети ' . $name,
            'code' => 'FAC-' . strtoupper(substr($code, 0, 3)),
            'is_active' => true,
        ]);

        $department = Department::create([
            'faculty_id' => $faculty->id,
            'name' => 'Кафедраи ' . $name,
            'code' => 'DEP-' . strtoupper(substr($code, 0, 3)),
            'is_active' => true,
        ]);

        return Subject::create([
            'department_id' => $department->id,
            'name' => $name,
            'code' => $code,
            'credits' => 3,
            'total_hours' => 60,
            'lecture_hours' => 30,
            'practice_hours' => 30,
            'is_active' => true,
        ]);
    }

    public function test_rating_questions_index_loads(): void
    {
        $user = $this->createAdmin();

        $response = $this->actingAs($user)->get('/admin/rating-questions');

        $response->assertStatus(200);
        $response->assertSee('Саволномаҳои рейтинг');
    }

    public function test_import_form_loads(): void
    {
        $user = $this->createAdmin();

        $response = $this->actingAs($user)->get('/admin/rating-questions/import');

        $response->assertStatus(200);
        $response->assertSee('Импорти саволҳои рейтинг');
    }

    public function test_can_import_rating_questions_via_csv(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF101');

        $csvContent = "question_text,options,correct,difficulty_level,explanation\n";
        $csvContent .= "Саволи тестӣ?,Варианти 1|Варианти 2|Варианти 3|Варианти 4,2,1,Сабаби тест\n";

        $response = $this->actingAs($user)->post('/admin/rating-questions/import', [
            'subject_id' => $subject->id,
            'file' => UploadedFile::fake()->createWithContent('test.csv', $csvContent),
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('questions', [
            'subject_id' => $subject->id,
            'question_text' => 'Саволи тестӣ?',
        ]);

        $bank = QuestionBank::where('subject_id', $subject->id)
            ->where('bank_type', 'rating')
            ->first();
        $this->assertNotNull($bank);

        $question = Question::where('question_bank_id', $bank->id)->first();
        $this->assertNotNull($question);
        $this->assertEquals(1, $question->answerOptions->where('is_correct', true)->count());
    }

    public function test_can_export_rating_questions(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF102');

        $bank = QuestionBank::create([
            'subject_id' => $subject->id,
            'name' => 'Рейтинг: ' . $subject->name,
            'bank_type' => 'rating',
            'teacher_id' => $user->id,
            'is_active' => true,
        ]);

        $question = Question::create([
            'question_bank_id' => $bank->id,
            'subject_id' => $subject->id,
            'type' => 'single_choice',
            'question_text' => 'Саволи экспорт?',
            'points' => 2.5,
            'is_active' => true,
        ]);

        AnswerOption::create([
            'question_id' => $question->id,
            'option_text' => 'А',
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        AnswerOption::create([
            'question_id' => $question->id,
            'option_text' => 'Б',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($user)->get('/admin/rating-questions/export?subject_id=' . $subject->id);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
