<?php

namespace Tests\Unit;

use App\Services\AvatarService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Санҷиши коркарди акс — бе вобастагии ба базаи додаҳо.
 *
 * Ин тест ба RefreshDatabase ниёҳ намекунад, зеро як қатори
 * мигратсияҳои лоиҳа (MySQL-only) дар SQLite кор намекунанд.
 */
class AvatarServiceTest extends TestCase
{
    private AvatarService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AvatarService::DISK);

        $this->service = new AvatarService;
    }

    protected function tearDown(): void
    {
        foreach (glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'avt_*') ?: [] as $f) {
            @unlink($f);
        }

        parent::tearDown();
    }

    // ==================== ВАРЗИШТОРҲО ====================

    private function image(string $format = 'jpg', int $w = 900, int $h = 700): UploadedFile
    {
        $im = imagecreatetruecolor($w, $h);

        // Растани шаблонӣ, то тасдиқ кардан, ки акс воқеан тасдиқ аст
        imagefill($im, 0, 0, imagecolorallocate($im, 30, 90, 180));
        imagefilledrectangle($im, 0, 0, (int) ($w / 2), (int) ($h / 2),
            imagecolorallocate($im, 250, 210, 70));
        imagefilledellipse($im, (int) ($w * 0.7), (int) ($h * 0.3), (int) ($w / 3), (int) ($h / 3),
            imagecolorallocate($im, 20, 160, 90));

        $path = tempnam(sys_get_temp_dir(), 'avt').'.'.$format;

        match ($format) {
            'png' => imagepng($im, $path),
            'webp' => imagewebp($im, $path, 92),
            default => imagejpeg($im, $path, 92),
        };

        imagedestroy($im);

        return new UploadedFile($path, basename($path), null, null, true);
    }

    private function fakeFile(string $name, string $contents = 'not-an-image'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'avt');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, null, null, true);
    }

    // ==================== ТЕСТҲО ====================

    public function test_uploads_jpeg(): void
    {
        $path = $this->service->store($this->image('jpg'));

        Storage::disk(AvatarService::DISK)->assertExists($path);
        $this->assertStringStartsWith(AvatarService::DIRECTORY.'/', $path);
    }

    public function test_uploads_png(): void
    {
        $path = $this->service->store($this->image('png'));

        Storage::disk(AvatarService::DISK)->assertExists($path);
    }

    public function test_uploads_webp(): void
    {
        $path = $this->service->store($this->image('webp'));

        Storage::disk(AvatarService::DISK)->assertExists($path);
    }

    public function test_output_is_512x512_for_landscape_source(): void
    {
        $path = $this->service->store($this->image('jpg', 1600, 900));
        [$w, $h] = getimagesize(Storage::disk(AvatarService::DISK)->path($path));

        $this->assertSame(512, $w);
        $this->assertSame(512, $h);
    }

    public function test_output_is_square_for_portrait_source(): void
    {
        $path = $this->service->store($this->image('jpg', 800, 2000));
        [$w, $h] = getimagesize(Storage::disk(AvatarService::DISK)->path($path));

        $this->assertSame(512, $w);
        $this->assertSame(512, $h, 'Акс бояд ба мувофиқ карда шавад, на танҳо ба 512 кӯтоҳ шавад.');
    }

    public function test_large_image_is_compressed_to_small_file(): void
    {
        $source = $this->image('jpg', 4000, 6000);
        $originalBytes = filesize($source->getRealPath());

        $path = $this->service->store($source);
        $finalBytes = Storage::disk(AvatarService::DISK)->size($path);

        $this->assertLessThanOrEqual(250 * 1024, $finalBytes, 'Акси ниҳоӣ бояд хурд бошад.');
        $this->assertLessThan($originalBytes, $finalBytes);
    }

    public function test_stored_format_is_webp_when_supported(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('WEBP дастгирӣ намешавад.');
        }

        $path = $this->service->store($this->image('png'));

        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame('image/webp', Storage::disk(AvatarService::DISK)->mimeType($path));
    }

    public function test_stored_filename_is_unique_and_safe(): void
    {
        $source = $this->image('jpg');
        $sourceName = $source->getClientOriginalName();

        $a = $this->service->store($source);
        $b = $this->service->store($this->image('jpg'));

        $this->assertNotSame($a, $b);
        // Номи асли корбар ҳеҷ гоҳ истифода намешавад ва ҳеҷ ҷои хароҷ нест
        $this->assertStringNotContainsString('..', $a);
        $this->assertStringNotContainsString('\\', $a);
        $this->assertStringNotContainsString(' ', $a);
        $this->assertStringNotContainsString($sourceName, $a);
        // Танҳо як хатти '/' байни папка ва файл истифода мешавад
        $this->assertSame(1, substr_count($a, '/'));
        $this->assertMatchesRegularExpression(
            '#^students/[0-9a-f-]{36}\.(webp|jpg)$#',
            $a
        );
    }

    public function test_rejects_non_image_content(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->store($this->fakeFile('evil.jpg', '<?php echo "pwned"; ?>'));
    }

    public function test_rejects_svg(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->expectException(\RuntimeException::class);

        $this->service->store($this->fakeFile('vector.svg', $svg));
    }

    public function test_rejects_disguised_executable(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->store($this->fakeFile('shell.php.jpg', "#!/bin/sh\nrm -rf /"));
    }

    public function test_replacement_does_not_accumulate_files(): void
    {
        $first = $this->service->store($this->image('jpg'));
        $second = $this->service->store($this->image('png'));

        $this->service->delete($first);

        Storage::disk(AvatarService::DISK)->assertMissing($first);
        Storage::disk(AvatarService::DISK)->assertExists($second);
        $this->assertCount(1, Storage::disk(AvatarService::DISK)->files(AvatarService::DIRECTORY));
    }

    public function test_delete_ignores_null(): void
    {
        $this->service->delete(null);

        $this->assertTrue(true);
    }

    public function test_delete_never_removes_shared_asset(): void
    {
        Storage::disk(AvatarService::DISK)->put('images/logo.png', 'shared-logo');

        $this->service->delete('images/logo.png');

        Storage::disk(AvatarService::DISK)->assertExists('images/logo.png');
    }

    public function test_delete_never_removes_path_traversal(): void
    {
        Storage::disk(AvatarService::DISK)->put('students/keep.txt', 'keep');

        $this->service->delete('students/../../../etc/passwd');
        $this->service->delete('../../../storage/logs/laravel.log');

        Storage::disk(AvatarService::DISK)->assertExists('students/keep.txt');
    }

    public function test_is_managed_avatar_recognises_only_our_files(): void
    {
        $path = $this->service->store($this->image('jpg'));

        $this->assertTrue($this->service->isManagedAvatar($path));
        $this->assertFalse($this->service->isManagedAvatar(null));
        $this->assertFalse($this->service->isManagedAvatar('images/logo.png'));
        $this->assertFalse($this->service->isManagedAvatar('students/manual.jpg'));
        $this->assertFalse($this->service->isManagedAvatar('students/../logo.png'));
    }

    public function test_constant_limits_match_requirements(): void
    {
        $this->assertSame(512, AvatarService::SIZE);
        $this->assertSame(2048, AvatarService::MAX_KILOBYTES);
        $this->assertSame(['jpeg', 'jpg', 'png', 'webp'], AvatarService::ALLOWED_MIMES);
        $this->assertNotContains('svg', AvatarService::ALLOWED_MIMES);
    }
}
