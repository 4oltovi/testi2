<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Хидмати коркарди сурати профили донишҷӯ
 *
 * Акси ягона барои нишон додан дар avatar-и мудавфим коркард мешавад,
 * бинобар ин андозаи ниҳоӣ 512×512 ва формати WEBP (ё JPEG) мебошад.
 * Асли ин гигапикселӣ ҳеҷ гоҳ дар сервер нигоҳ дошта намешавад.
 */
class AvatarService
{
    /** Рақами фарқирот (МБ) */
    public const DISK = 'public';

    /** Папкаи нигоҳдории аксҳо дар диски public */
    public const DIRECTORY = 'students';

    /** Андозаи ниҳоии акс (пиксел) */
    public const SIZE = 512;

    /** Форматҳои қобулшуда — аз SVG ва форматҳои дигар хатарнок саркашӣ мекунад */
    public const ALLOWED_MIMES = ['jpeg', 'jpg', 'png', 'webp'];

    /** Ҳадди андозаи болоии файли боршуда (килобайт) */
    public const MAX_KILOBYTES = 2048;

    /** Сифати WEBP барои avatar (бод дар баланд будан, баландтар аз ин нест) */
    private const WEBP_QUALITY = 82;

    /**
     * Акси боршударо коркард, оптимизатсия ва нигоҳ мекунад.
     *
     * @return string Номи файли дар диски (масалан: students/9f2c...webp)
     *
     * @throws \RuntimeException Агар файл воқеан тасдиқшунавандаи JPEG/PNG/WebP набошад
     */
    public function store(UploadedFile $file): string
    {
        $source = $this->decode($file);

        try {
            $image = $this->makeSquare($source);
        } finally {
            imagedestroy($source);
        }

        try {
            $filename = $this->writeToDisk($image);
        } finally {
            imagedestroy($image);
        }

        return $filename;
    }

    /**
     * Акси кӯҳнаро аз диски бартараф мекунад.
     *
     * Танҳо файлҳое нест карда мешаванд, ки дар папкаи мо барои avatar
     * сохта шудаанд — аксҳои дигар (масалун логотип) дастнохӣ намешаванд.
     */
    public function delete(?string $filename): void
    {
        if (! $this->isManagedAvatar($filename)) {
            return;
        }

        Storage::disk(self::DISK)->delete($filename);
    }

    /**
     * Оё ин ном акс аз ҷои мо мебошад?
     */
    public function isManagedAvatar(?string $filename): bool
    {
        if (! $filename) {
            return false;
        }

        $prefix = self::DIRECTORY.'/';

        if (! str_starts_with($filename, $prefix)) {
            return false;
        }

        // Ном бояд бо UUID-и мо ва паҳноии коркардшуда ба ҷамъ шавад
        return (bool) preg_match(
            '#^'.preg_quote($prefix, '#').'[0-9a-f-]{36}\.(webp|jpg)$#',
            $filename
        );
    }

    /**
     * Файлро мехонад ва ба GD медиҳад.
     *
     * Навъи воқеи тасдиқ мешавад, на танҳо паҳнои файл — бинобар ин
     * файл муфарқӣ метавонад ба ҷои акс ҷойгир шавад.
     *
     *
     * @throws \RuntimeException
     */
    private function decode(UploadedFile $file): \GdImage
    {
        $contents = @file_get_contents($file->getRealPath());

        if ($contents === false || $contents === '') {
            throw new \RuntimeException('Файли боршуда хонда нашуд.');
        }

        $info = @getimagesizefromstring($contents);

        if ($info === false) {
            throw new \RuntimeException('Файл ин тасдиқшунавандаи тасдиқ мебошад.');
        }

        // Аз тасдиқкунонии андоза — пеш аз он ки файл воқеан тасдиқ аст
        if (! in_array(strtolower($info['mime']), $this->allowedMimes(), true)) {
            throw new \RuntimeException('Формат ин қобулшуда нест.');
        }

        $image = match (strtolower($info['mime'])) {
            'image/jpeg' => @imagecreatefromstring($contents),
            'image/png' => @imagecreatefromstring($contents),
            'image/webp' => @imagecreatefromstring($contents),
            default => false,
        };

        if ($image === false) {
            throw new \RuntimeException('Файли тасдиқшуда коркард нашуд.');
        }

        return $image;
    }

    /**
     * @return array<int, string>
     */
    private function allowedMimes(): array
    {
        return array_map(
            fn (string $ext): string => 'image/'.$ext,
            self::ALLOWED_MIMES
        );
    }

    /**
     * Акси боршударо ба муҳаққақан мувфиқ (square) мекарояд ва ба 512×512
     * тангим мекунад. Тангим аз маркаб (center crop) истифода мебарад, ки
     * дар натиҷа акс ҳам баланд ва ҳам варақ намешавад.
     */
    private function makeSquare(\GdImage $source): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);

        $side = min($width, $height);
        $cropX = (int) (($width - $side) / 2);
        $cropY = (int) (($height - $side) / 2);

        $target = imagecreatetruecolor(self::SIZE, self::SIZE);

        if ($target === false) {
            throw new \RuntimeException('Тасдири акс сохта нашуд.');
        }

        // Заминаи сафед барои PNG/WebP бо шаффобият — то JPEG-и хутос
        // сиёҳ нашавад
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 255, 255, 255, 127));

        $resampled = imagecopyresampled(
            $target, $source,
            0, 0,
            $cropX, $cropY,
            self::SIZE, self::SIZE,
            $side, $side
        );

        if (! $resampled) {
            imagedestroy($target);
            throw new \RuntimeException('Акси коркард нашуд.');
        }

        return $target;
    }

    /**
     * Акси коркардшударо дар диски public менувисад.
     */
    private function writeToDisk(\GdImage $image): string
    {
        $uuid = (string) Str::uuid();

        $extension = $this->supportsWebp() ? 'webp' : 'jpg';
        $filename = self::DIRECTORY.'/'.$uuid.'.'.$extension;

        $directory = self::DIRECTORY;

        if (! Storage::disk(self::DISK)->exists($directory)) {
            Storage::disk(self::DISK)->makeDirectory($directory);
        }

        ob_start();

        if ($extension === 'webp') {
            imagewebp($image, null, self::WEBP_QUALITY);
        } else {
            imagejpeg($image, null, self::WEBP_QUALITY);
        }

        $bytes = (string) ob_get_clean();

        if ($bytes === '') {
            throw new \RuntimeException('Акс ҳамчун файл коди карда нашуд.');
        }

        $disk = Storage::disk(self::DISK);

        if (! $disk->put($filename, $bytes)) {
            throw new \RuntimeException('Акс нигоҳ дар дастгоҳ нашуд.');
        }

        return $filename;
    }

    /**
     * Оё WEBP дар муҳити ҷорӣ дастгирӣ мешавад?
     */
    private function supportsWebp(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromwebp');
    }
}
