<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Role;
use App\Models\User;
use App\Models\Subject;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Question;
use App\Models\AnswerOption;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QuestionExcelImportTest extends TestCase
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
        $faculty = Faculty::create([
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

    private function createMarkerExcel(array $questions, string $filename = 'test.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $rowNum = 1;
        foreach ($questions as $question) {
            $sheet->setCellValue('A' . $rowNum, '@' . $question['text']);
            $rowNum++;

            foreach ($question['options'] as $option) {
                $prefix = $option['correct'] ? '$' : '&';
                $sheet->setCellValue('A' . $rowNum, $prefix . $option['text']);
                $rowNum++;
            }

            $rowNum++;
        }

        $tmpPath = sys_get_temp_dir() . '/' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return UploadedFile::fake()->createWithContent($filename, file_get_contents($tmpPath));
    }

    public function test_import_form_loads(): void
    {
        $user = $this->createAdmin();

        $response = $this->actingAs($user)->get('/admin/questions/excel-import');

        $response->assertStatus(200);
        $response->assertSee('Импорти саволҳо аз Excel');
    }

    public function test_simple_question_with_4_options(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF101');

        $questions = [
            [
                'text' => 'Пойтахти Тоҷикистон кадом шаҳр аст?',
                'options' => [
                    ['text' => 'Душанбе', 'correct' => true],
                    ['text' => 'Хуҷанд', 'correct' => false],
                    ['text' => 'Бохтар', 'correct' => false],
                    ['text' => 'Кӯлоб', 'correct' => false],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $response = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $previewResponse = $this->get($response->headers->get('Location'));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Пой');
    }

    public function test_can_confirm_import_simple_question(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF102');

        $questions = [
            [
                'text' => 'Савол?',
                'options' => [
                    ['text' => 'В1', 'correct' => true],
                    ['text' => 'В2', 'correct' => false],
                    ['text' => 'В3', 'correct' => false],
                    ['text' => 'В4', 'correct' => false],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $uploadResponse = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $this->actingAs($user)->post('/admin/questions/excel-import/confirm', ['confirm' => '1']);

        $this->assertDatabaseHas('questions', [
            'subject_id' => $subject->id,
            'question_text' => 'Савол?',
            'type' => 'single_choice',
        ]);
    }

    public function test_simple_question_correct_answer_is_option_1(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF103');

        $questions = [
            [
                'text' => '2+2 = ?',
                'options' => [
                    ['text' => '4', 'correct' => true],
                    ['text' => '3', 'correct' => false],
                    ['text' => '5', 'correct' => false],
                    ['text' => '6', 'correct' => false],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $uploadResponse = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $this->actingAs($user)->post('/admin/questions/excel-import/confirm', ['confirm' => '1']);

        $question = Question::where('question_text', '2+2 = ?')->first();
        $this->assertNotNull($question);
        $this->assertEquals('4', $question->answerOptions->where('is_correct', true)->first()->option_text);
    }

    public function test_simple_question_correct_answer_is_option_4(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF104');

        $questions = [
            [
                'text' => 'Кадом забон барои веб истифода мешавад?',
                'options' => [
                    ['text' => 'Python', 'correct' => false],
                    ['text' => 'Java', 'correct' => false],
                    ['text' => 'HTML', 'correct' => false],
                    ['text' => 'PHP', 'correct' => true],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $uploadResponse = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $this->actingAs($user)->post('/admin/questions/excel-import/confirm', ['confirm' => '1']);

        $question = Question::where('question_text', 'Кадом забон барои веб истифода мешавад?')->first();
        $this->assertNotNull($question);
        $this->assertEquals('PHP', $question->answerOptions->where('is_correct', true)->first()->option_text);
    }

    public function test_simple_question_with_5_options(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF105');

        $questions = [
            [
                'text' => 'Кадом календар истифода мешавад?',
                'options' => [
                    ['text' => 'Ҳиҷрӣ', 'correct' => false],
                    ['text' => 'Шамсӣ', 'correct' => false],
                    ['text' => 'Лунӣ', 'correct' => false],
                    ['text' => 'Миллӣ', 'correct' => false],
                    ['text' => 'Григориан', 'correct' => true],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $uploadResponse = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $this->actingAs($user)->post('/admin/questions/excel-import/confirm', ['confirm' => '1']);

        $question = Question::where('question_text', 'Кадом календар истифода мешавад?')->first();
        $this->assertNotNull($question);
        $this->assertEquals(5, $question->answerOptions->count());
        $this->assertEquals('Григориан', $question->answerOptions->where('is_correct', true)->first()->option_text);
    }

    public function test_simple_question_with_marker_based_answers(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF106');

        $questions = [
            [
                'text' => 'Пойтахти Тоҷикистон кадом шаҳр аст?',
                'options' => [
                    ['text' => 'Душанбе', 'correct' => true],
                    ['text' => 'Хуҷанд', 'correct' => false],
                    ['text' => 'Бохтар', 'correct' => false],
                    ['text' => 'Кӯлоб', 'correct' => false],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $uploadResponse = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $this->actingAs($user)->post('/admin/questions/excel-import/confirm', ['confirm' => '1']);

        $question = Question::where('question_text', 'Пойтахти Тоҷикистон кадом шаҳр аст?')->first();
        $this->assertNotNull($question);
        $this->assertEquals(4, $question->answerOptions->count());
        $this->assertEquals('Душанбе', $question->answerOptions->where('is_correct', true)->first()->option_text);
    }

    public function test_multiple_questions_in_one_excel(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF107');

        $questions = [
            [
                'text' => 'Саволи якум?',
                'options' => [
                    ['text' => 'В1', 'correct' => true],
                    ['text' => 'В2', 'correct' => false],
                ],
            ],
            [
                'text' => 'Саволи дуюм?',
                'options' => [
                    ['text' => 'В3', 'correct' => true],
                    ['text' => 'В4', 'correct' => false],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $uploadResponse = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $this->actingAs($user)->post('/admin/questions/excel-import/confirm', ['confirm' => '1']);

        $this->assertDatabaseHas('questions', ['question_text' => 'Саволи якум?', 'type' => 'single_choice']);
        $this->assertDatabaseHas('questions', ['question_text' => 'Саволи дуюм?', 'type' => 'single_choice']);
    }

    public function test_empty_question_is_skipped(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF108');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '@');
        $sheet->setCellValue('A2', '$Душанбе');
        $sheet->setCellValue('A3', '&Хуҷанд');
        $sheet->setCellValue('A5', '@Савол 2?');
        $sheet->setCellValue('A6', '$В1');
        $sheet->setCellValue('A7', '&В2');

        $tmpPath = sys_get_temp_dir() . '/' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);
        $file = UploadedFile::fake()->createWithContent('empty.xlsx', file_get_contents($tmpPath));

        $response = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $response->assertSessionHas('import_errors');
    }

    public function test_missing_correct_answer_shows_error(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF109');

        $questions = [
            [
                'text' => 'Савол?',
                'options' => [
                    ['text' => 'В1', 'correct' => false],
                    ['text' => 'В2', 'correct' => false],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $response = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $response->assertSessionHas('import_errors');
    }

    public function test_multiple_correct_answers_shows_error(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF110');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '@Савол?');
        $sheet->setCellValue('A2', '$В1');
        $sheet->setCellValue('A3', '$В2');
        $sheet->setCellValue('A4', '&В3');

        $tmpPath = sys_get_temp_dir() . '/' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);
        $file = UploadedFile::fake()->createWithContent('multi.xlsx', file_get_contents($tmpPath));

        $response = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $response->assertSessionHas('import_errors');
    }

    public function test_too_few_options_shows_error(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF111');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '@Савол?');
        $sheet->setCellValue('A2', '$В1');

        $tmpPath = sys_get_temp_dir() . '/' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);
        $file = UploadedFile::fake()->createWithContent('few.xlsx', file_get_contents($tmpPath));

        $response = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $response->assertSessionHas('import_errors');
    }

    public function test_duplicate_options_shows_warning(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF112');

        $questions = [
            [
                'text' => 'Савол?',
                'options' => [
                    ['text' => 'В1', 'correct' => true],
                    ['text' => 'В1', 'correct' => false],
                    ['text' => 'В2', 'correct' => false],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $response = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $response->assertSessionHas('import_errors');
    }

    public function test_empty_row_is_skipped(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF113');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '@Савол 1?');
        $sheet->setCellValue('A2', '$В1');
        $sheet->setCellValue('A3', '&В2');
        $sheet->setCellValue('A4', '');
        $sheet->setCellValue('A5', '@Савол 2?');
        $sheet->setCellValue('A6', '$В3');
        $sheet->setCellValue('A7', '&В4');

        $tmpPath = sys_get_temp_dir() . '/' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);
        $file = UploadedFile::fake()->createWithContent('empty_rows.xlsx', file_get_contents($tmpPath));

        $uploadResponse = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $previewUrl = $uploadResponse->headers->get('Location');
        $previewResponse = $this->get($previewUrl);
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Савол 1?');
        $previewResponse->assertSee('Савол 2?');
    }

    public function test_large_excel_file(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF114');

        $questions = [];
        for ($i = 1; $i <= 100; $i++) {
            $questions[] = [
                'text' => "Савол {$i}?",
                'options' => [
                    ['text' => 'В1', 'correct' => true],
                    ['text' => 'В2', 'correct' => false],
                    ['text' => 'В3', 'correct' => false],
                    ['text' => 'В4', 'correct' => false],
                ],
            ];
        }

        $file = $this->createMarkerExcel($questions);

        $uploadResponse = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $previewUrl = $uploadResponse->headers->get('Location');
        $previewResponse = $this->get($previewUrl);
        $previewResponse->assertStatus(200);

        $this->actingAs($user)->post('/admin/questions/excel-import/confirm', ['confirm' => '1']);
        $this->assertEquals(100, Question::where('subject_id', $subject->id)->count());
    }

    public function test_mixed_languages_and_symbols(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF115');

        $questions = [
            [
                'text' => 'Which symbol is used for a variable in PHP?',
                'options' => [
                    ['text' => '#', 'correct' => false],
                    ['text' => '$', 'correct' => true],
                    ['text' => '%', 'correct' => false],
                ],
            ],
            [
                'text' => 'Пойтахти Тоҷикистон?',
                'options' => [
                    ['text' => 'Душанбе', 'correct' => true],
                    ['text' => 'Хуҷанд', 'correct' => false],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $uploadResponse = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $this->actingAs($user)->post('/admin/questions/excel-import/confirm', ['confirm' => '1']);

        $this->assertDatabaseHas('questions', ['question_text' => 'Which symbol is used for a variable in PHP?', 'type' => 'single_choice']);
        $this->assertDatabaseHas('questions', ['question_text' => 'Пойтахти Тоҷикистон?', 'type' => 'single_choice']);
    }

    public function test_preview_page_loads(): void
    {
        $user = $this->createAdmin();
        $subject = $this->createSubject('Тест Фан', 'TF116');

        $questions = [
            [
                'text' => 'Савол?',
                'options' => [
                    ['text' => 'В1', 'correct' => true],
                    ['text' => 'В2', 'correct' => false],
                ],
            ],
        ];

        $file = $this->createMarkerExcel($questions);

        $response = $this->actingAs($user)->post('/admin/questions/excel-import', [
            'subject_id' => $subject->id,
            'file' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('/admin/questions/excel-import/preview', $redirectUrl);

        $previewResponse = $this->actingAs($user)->get($redirectUrl);
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Савол?');
    }
}
