<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentAvatarTest extends TestCase
{
    use RefreshDatabase;

    private const DISK = 'public';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(self::DISK);

        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD бо WEBP дар ин муҳит дастгирӣ намешавад.');
        }
    }

    // ==================== ВОРИДШАВ ====================

    private array $org = [];

    /**
     * Заминаи пурраи аниқсозӣ: донишгоҳ → кафедра → ихтисос → курс → гурӯҳ
     */
    private function makeGroup(int $index = 1): Group
    {
        if ($this->org === []) {
            $year = AcademicYear::create([
                'name' => '2026-2027',
                'start_year' => 2026,
                'end_year' => 2027,
                'start_date' => '2026-09-01',
                'end_date' => '2027-08-31',
                'is_active' => true,
            ]);

            $faculty = Faculty::create([
                'name' => 'Факултети Саломат',
                'code' => 'FAC-TST',
                'is_active' => true,
            ]);

            $department = Department::create([
                'faculty_id' => $faculty->id,
                'name' => 'Кафедраи Саломат',
                'code' => 'DEP-TST',
                'is_active' => true,
            ]);

            $specialty = Specialty::create([
                'department_id' => $department->id,
                'faculty_id' => $faculty->id,
                'name' => 'Парасширӣ',
                'code' => 'SP-TST',
                'total_credits' => 120,
                'is_active' => true,
            ]);

            $course = Course::create([
                'number' => $index,
                'name' => 'Курси '.$index,
            ]);

            $this->org = compact('year', 'faculty', 'department', 'specialty', 'course');
        }

        return Group::create([
            'specialty_id' => $this->org['specialty']->id,
            'course_id' => $this->org['course']->id,
            'academic_year_id' => $this->org['year']->id,
            'name' => 'Гурӯҳи Тест'.$index,
            'code' => (string) $index,
            'is_active' => true,
        ]);
    }

    private function studentUser(int $index = 1, array $userAttrs = []): User
    {
        $role = Role::firstOrCreate([
            'name' => 'student',
            'display_name' => 'Донишҷӯ',
            'level' => 10,
            'is_system' => true,
        ]);

        $user = User::factory()->create(array_merge([
            'status' => 'active',
            'first_name' => 'Али',
            'last_name' => 'Каримов',
            'avatar' => null,
        ], $userAttrs));

        $user->roles()->attach($role->id);

        $group = $this->makeGroup($index);

        $student = $user->student()->create([
            'group_id' => $group->id,
            'specialty_id' => $group->specialty_id,
            'course_id' => $group->course_id,
            'student_id_number' => 'S'.random_int(1000, 9999),
            'enrollment_date' => now()->subYear()->toDateString(),
            'status' => 'active',
        ]);

        return $user->fresh()->setRelation('student', $student);
    }

    // ==================== ВАРЗИШТОРҲО ====================

    private function makeImage(string $format = 'jpg', int $w = 800, int $h = 600): UploadedFile
    {
        $image = imagecreatetruecolor($w, $h);
        imagefill($image, 0, 0, imagecolorallocate($image, 40, 120, 200));
        imagefilledellipse($image, (int) ($w / 2), (int) ($h / 2), (int) ($w / 2), (int) ($h / 2),
            imagecolorallocate($image, 240, 200, 60));

        $path = tempnam(sys_get_temp_dir(), 'avt').'.'.$format;

        match ($format) {
            'png' => imagepng($image, $path),
            'webp' => imagewebp($image, $path, 90),
            default => imagejpeg($image, $path, 90),
        };

        imagedestroy($image);

        return new UploadedFile($path, basename($path), null, null, true);
    }

    // ==================== ТЕСТҲО ====================

    public function test_student_without_photo_gets_default_avatar(): void
    {
        $user = $this->studentUser();

        $this->assertNull($user->avatar);

        // Partial-и умумӣ инитиалсҳоро нишон медиҳад, на тасвири холӣ
        $this->actingAs($user)
            ->get(route('student.profile'))
            ->assertOk()
            ->assertDontSee('<img src="'.Storage::disk(self::DISK)->url(''), false);

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('<img src="'.Storage::disk(self::DISK)->url(''), false);
    }

    public function test_student_with_photo_sees_image_on_profile_and_dashboard(): void
    {
        $user = $this->studentUser(1);

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')]);

        $path = $user->fresh()->avatar;
        Storage::disk(self::DISK)->assertExists($path);
        $url = Storage::disk(self::DISK)->url($path);

        $this->actingAs($user->fresh())
            ->get(route('student.profile'))
            ->assertOk()
            ->assertSee('<img src="'.$url.'"', false);

        $this->actingAs($user->fresh())
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('<img src="'.$url.'"', false);
    }

    public function test_student_can_upload_jpg(): void
    {
        $user = $this->studentUser();

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')])
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk(self::DISK)->assertExists($user->avatar);
    }

    public function test_student_can_upload_png(): void
    {
        $user = $this->studentUser();

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('png')])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->avatar);
    }

    public function test_student_can_upload_webp(): void
    {
        $user = $this->studentUser();

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('webp')])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->avatar);
    }

    public function test_large_image_is_resized_to_512_and_cropped_square(): void
    {
        $user = $this->studentUser();

        // Акси калӯнӣ — бояд мувофиқ карда шавад
        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg', 1200, 900)])
            ->assertRedirect();

        $path = Storage::disk(self::DISK)->path($user->fresh()->avatar);
        [$width, $height] = getimagesize($path);

        $this->assertSame(512, $width);
        $this->assertSame(512, $height, 'Акс бояд мувофиқ (square) бошад.');

        // Байтҳои аслӣ нестанд — танҳо натиҷаи коркардшуда
        $this->assertLessThanOrEqual(250 * 1024, filesize($path), 'Файл бояд хурд бошад.');
    }

    public function test_oversized_upload_is_rejected(): void
    {
        $user = $this->studentUser();

        $file = $this->makeImage('jpg', 400, 400);

        // Ҳаҷми файли ҳимоягиро аз 2 МБ зиёд мекунем
        $handle = fopen($file->getRealPath(), 'ab');
        fwrite($handle, str_repeat("\0", 3 * 1024 * 1024));
        fclose($handle);

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $file])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        $user = $this->studentUser();

        $file = UploadedFile::fake()->create('malicious.php', 10, 'application/x-php');

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $file])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_svg_is_rejected(): void
    {
        $user = $this->studentUser();

        $file = UploadedFile::fake()->create('vector.svg', 5, 'image/svg+xml');

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $file])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_fake_image_extension_is_rejected(): void
    {
        $user = $this->studentUser();

        // Файли матнӣ, ки паҳнои jpg дорад — на бояд қабул шавад
        $path = tempnam(sys_get_temp_dir(), 'fake');
        file_put_contents($path, '<?php echo "not an image"; ?>');

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), [
                'photo' => new UploadedFile($path, 'evil.jpg', 'image/jpeg', null, true),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_replacement_removes_old_photo(): void
    {
        $user = $this->studentUser();

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')]);
        $oldPhoto = $user->fresh()->avatar;
        Storage::disk(self::DISK)->assertExists($oldPhoto);

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('png')]);
        $newPhoto = $user->fresh()->avatar;

        $this->assertNotSame($oldPhoto, $newPhoto);
        Storage::disk(self::DISK)->assertMissing($oldPhoto);
        Storage::disk(self::DISK)->assertExists($newPhoto);
    }

    public function test_failed_upload_keeps_existing_photo(): void
    {
        $user = $this->studentUser();

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')]);
        $oldPhoto = $user->fresh()->avatar;

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), [
                'photo' => UploadedFile::fake()->create('bad.png', 10, 'image/png'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertSame($oldPhoto, $user->fresh()->avatar);
        Storage::disk(self::DISK)->assertExists($oldPhoto);
    }

    public function test_student_cannot_modify_another_students_photo(): void
    {
        $user = $this->studentUser();
        $victim = $this->studentUser(2, ['first_name' => 'Виктор', 'last_name' => 'Наимов']);

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')]);

        $victim->refresh();
        $this->assertNull($victim->avatar, 'Акси донишҷӯи дигар набояд тағйир ёбад.');

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), [
                'student_id' => $victim->student->id,
                'photo' => $this->makeImage('jpg'),
            ]);

        $this->assertNull($victim->fresh()->avatar);
    }

    public function test_guest_cannot_upload_photo(): void
    {
        $this->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')])
            ->assertRedirect(route('login'));
    }

    public function test_student_can_remove_own_photo(): void
    {
        $user = $this->studentUser();

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')]);
        $photo = $user->fresh()->avatar;
        Storage::disk(self::DISK)->assertExists($photo);

        $this->actingAs($user)
            ->delete(route('student.profile.photo.destroy'))
            ->assertRedirect();

        $this->assertNull($user->fresh()->avatar);
        Storage::disk(self::DISK)->assertMissing($photo);
    }

    public function test_remove_never_deletes_shared_asset(): void
    {
        $user = $this->studentUser(1, ['avatar' => 'images/logo.png']);

        Storage::disk(self::DISK)->put('images/logo.png', 'shared');

        $this->actingAs($user)->delete(route('student.profile.photo.destroy'));

        $this->assertNull($user->fresh()->avatar);
        Storage::disk(self::DISK)->assertExists('images/logo.png');
    }

    public function test_dashboard_still_loads_after_upload(): void
    {
        $user = $this->studentUser();

        $this->actingAs($user)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')]);

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk();
    }

    public function test_profile_page_still_loads(): void
    {
        $user = $this->studentUser();

        $this->actingAs($user)
            ->get(route('student.profile'))
            ->assertOk();
    }

    // ============ НЕСТ КАРДАНИ АКС АЗ ТАРАФИ АДМИН ============

    private function adminUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'status' => 'active',
            'first_name' => 'Админ',
            'last_name' => 'Тест',
            'avatar' => null,
        ], $attrs));
    }

    private function grantSuperAdmin(User $user): void
    {
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'display_name' => 'Суперадмин',
            'level' => 100,
            'is_system' => true,
        ]);

        $user->roles()->attach($role->id);
    }

    private function grantStudentsEdit(User $user): void
    {
        $role = Role::firstOrCreate([
            'name' => 'moderator',
            'display_name' => 'Модератор',
            'level' => 50,
            'is_system' => false,
        ]);

        $permission = Permission::firstOrCreate([
            'name' => 'students.edit',
        ], [
            'display_name' => 'Таҳрири донишҷӯ',
            'module' => 'students',
        ]);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);
    }

    public function test_super_admin_can_delete_student_avatar(): void
    {
        $student = $this->studentUser();

        $this->actingAs($student)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')]);
        $photo = $student->fresh()->avatar;
        Storage::disk(self::DISK)->assertExists($photo);

        $admin = $this->adminUser();
        $this->grantSuperAdmin($admin);

        $this->actingAs($admin)
            ->delete(route('admin.students.avatar.destroy', $student->student))
            ->assertRedirect();

        $this->assertNull($student->fresh()->avatar);
        Storage::disk(self::DISK)->assertMissing($photo);
    }

    public function test_admin_with_students_edit_permission_can_delete_student_avatar(): void
    {
        // Ном акс бояд бо шаблони UUID-и AvatarService мувофиқ бошад
        $path = 'students/3f2504e0-4f89-11d3-9a0c-0305e82c3301.jpg';
        $student = $this->studentUser(2, ['avatar' => $path]);
        Storage::disk(self::DISK)->put($path, 'x');

        $admin = $this->adminUser();
        $this->grantStudentsEdit($admin);

        $this->actingAs($admin)
            ->delete(route('admin.students.avatar.destroy', $student->student))
            ->assertRedirect();

        $this->assertNull($student->fresh()->avatar);
        Storage::disk(self::DISK)->assertMissing($path);
    }

    public function test_admin_avatar_removal_skips_unmanaged_file_inside_students_folder(): void
    {
        // Ҳам дар папкаи avatars, аммо ном ба шаблони мо мувофиқ нест -> дастнохӣ мемонад
        $path = 'students/not-a-managed-avatar.jpg';
        $student = $this->studentUser(7, ['avatar' => $path]);
        Storage::disk(self::DISK)->put($path, 'x');

        $admin = $this->adminUser();
        $this->grantSuperAdmin($admin);

        $this->actingAs($admin)
            ->delete(route('admin.students.avatar.destroy', $student->student))
            ->assertRedirect();

        $this->assertNull($student->fresh()->avatar);
        Storage::disk(self::DISK)->assertExists($path);
    }

    public function test_admin_without_permission_cannot_delete_student_avatar(): void
    {
        $student = $this->studentUser(3, ['avatar' => 'students/locked.jpg']);
        Storage::disk(self::DISK)->put('students/locked.jpg', 'x');

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->delete(route('admin.students.avatar.destroy', $student->student))
            ->assertForbidden();

        $this->assertSame('students/locked.jpg', $student->fresh()->avatar);
        Storage::disk(self::DISK)->assertExists('students/locked.jpg');
    }

    public function test_guest_cannot_delete_student_avatar(): void
    {
        $student = $this->studentUser(4, ['avatar' => 'students/anonymous.jpg']);
        Storage::disk(self::DISK)->put('students/anonymous.jpg', 'x');

        $this->delete(route('admin.students.avatar.destroy', $student->student))
            ->assertForbidden();

        $this->assertSame('students/anonymous.jpg', $student->fresh()->avatar);
        Storage::disk(self::DISK)->assertExists('students/anonymous.jpg');
    }

    public function test_admin_avatar_removal_never_deletes_shared_asset(): void
    {
        $student = $this->studentUser(5, ['avatar' => 'images/logo.png']);
        Storage::disk(self::DISK)->put('images/logo.png', 'shared');

        $admin = $this->adminUser();
        $this->grantSuperAdmin($admin);

        $this->actingAs($admin)
            ->delete(route('admin.students.avatar.destroy', $student->student))
            ->assertRedirect();

        $this->assertNull($student->fresh()->avatar);
        Storage::disk(self::DISK)->assertExists('images/logo.png');
    }

    // ============ СИФАТИ АКС ДАР САҲФАҲО ============

    public function test_admin_show_page_renders_large_avatar(): void
    {
        $student = $this->studentUser(6);

        $this->actingAs($student->fresh())
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')]);

        $url = Storage::disk(self::DISK)->url($student->fresh()->avatar);

        $admin = $this->adminUser();
        $this->grantSuperAdmin($admin);

        $html = $this->actingAs($admin->fresh())
            ->get(route('admin.students.show', $student->student))
            ->assertOk()
            ->getContent();

        // Акс дар саҳифа ҳаст
        $this->assertStringContainsString('<img src="'.$url.'"', $html);

        // Чӣ хеле калон: 144px акс + 3px кадр дар ҳар тараф = 150px
        $this->assertStringContainsString('admin-student-photo', $html);
        $this->assertStringContainsString('width: 150px;', $html);
        $this->assertStringContainsString('height: 150px;', $html);
        $this->assertStringContainsString('border: 3px solid #fff;', $html);
        $this->assertStringContainsString('box-shadow: 0 .25rem .75rem', $html);

        // Акс аз partial 144px ва object-fit: cover -> тарҳӣ нешавад
        $this->assertStringContainsString('width: 144px; height: 144px; object-fit: cover;', $html);

        // Линки профил танҳо барои панели донишҷӯ аст — админ нагинад
        $this->assertStringNotContainsString('student-profile-link', $html);
    }

    public function test_admin_student_index_shows_name_only_without_avatar(): void
    {
        $student = $this->studentUser(7);

        $this->actingAs($student)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')]);

        $url = Storage::disk(self::DISK)->url($student->fresh()->avatar);

        $admin = $this->adminUser();
        $this->grantSuperAdmin($admin);

        $html = $this->actingAs($admin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->getContent();

        // Ном ҳаст, аммо акс нест
        $this->assertStringContainsString($student->full_name, $html);
        $this->assertStringNotContainsString('<img src="'.$url.'"', $html);
    }

    public function test_student_search_json_has_no_avatar_fields(): void
    {
        $student = $this->studentUser(8);

        $this->actingAs($student)
            ->post(route('student.profile.photo.update'), ['photo' => $this->makeImage('jpg')]);

        $admin = $this->adminUser();
        $this->grantSuperAdmin($admin);

        $data = $this->actingAs($admin)
            ->getJson(route('admin.students.search', ['search' => 'Каримов']))
            ->assertOk()
            ->json();

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayNotHasKey('avatar_url', $data[0]);
        $this->assertArrayNotHasKey('initials', $data[0]);
    }

    // ============ ЛИНКИ ПРОФИЛ ДАР ПАНЕЛИ ДОНИШҶӮ ============

    public function test_student_header_name_links_to_profile(): void
    {
        $user = $this->studentUser(9);

        $html = $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->getContent();

        // Ном дар навор ба профил пайваст аст
        $this->assertStringContainsString('student-profile-link', $html);
        $this->assertStringContainsString('href="'.route('student.profile').'"', $html);
        $this->assertStringContainsString('aria-label="Профил"', $html);
    }

    public function test_student_sidebar_no_longer_has_profile_item(): void
    {
        $user = $this->studentUser(10);

        $html = $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->getContent();

        // Аён "Профил" аз менюи sidebar нест, аммо линки сарлавҳа ҳаст
        $this->assertStringNotContainsString('>Профил<', $html);
        $this->assertStringContainsString('student-profile-link', $html);

        // Қолгоҳи меню ба ҳамон тартиб
        $this->assertStringContainsString('Панели асосӣ', $html);
        $this->assertStringContainsString('Баҳоҳои ман', $html);

        // Тугмаи "Профили ман" дар карточкаи панел ҳаст
        $this->assertStringContainsString('Профили ман', $html);
    }

    public function test_student_dashboard_photo_button_still_present(): void
    {
        $user = $this->studentUser(11);

        $html = $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('href="'.route('student.profile').'"', $html);
        $this->assertStringContainsString('Профили ман', $html);
    }

    public function test_admin_show_page_loads_and_lists_avatar_control(): void
    {
        $student = $this->studentUser(6);
        $admin = $this->adminUser();
        $this->grantSuperAdmin($admin);

        // Акс нест -> тугмаи нест кардан набояд
        $this->actingAs($admin)
            ->get(route('admin.students.show', $student->student))
            ->assertOk()
            ->assertDontSee(route('admin.students.avatar.destroy', $student->student), false);

        $student->update(['avatar' => 'students/visible.jpg']);
        Storage::disk(self::DISK)->put('students/visible.jpg', 'x');

        // Акс ҳаст -> тугмаи нест кардан бояд
        $this->actingAs($admin)
            ->get(route('admin.students.show', $student->fresh()->student))
            ->assertOk()
            ->assertSee(route('admin.students.avatar.destroy', $student->fresh()->student), false);
    }
}
