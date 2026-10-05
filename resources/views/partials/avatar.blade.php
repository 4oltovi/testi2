{{--
    Акси профили корбар (ё инитиалсҳо агар акс набвашад).

    Параметрҳо:
      $user  — модели User (ё Student, ки ->user дорад)
      $size  — диаметр дар пиксел (ба таври пешфаз 36)

    Ради баланд кардани самараи кеш, ҳуҷати вуҷуд будани файл
    танҳо як маротиба дар ҳар дархост санҷида мешавад.
--}}
@php
    $avatarUser = $user ?? null;
    $avatarSize = (int) ($size ?? 36);
    $avatarDisk = \Illuminate\Support\Facades\Storage::disk('public');

    // Дар як дархост танҳо як бор самара месӣ шавад
    static $avatarFileChecks = [];

    $avatarPath = $avatarUser?->avatar;
    $avatarHasFile = false;

    if ($avatarPath) {
        if (! array_key_exists($avatarPath, $avatarFileChecks)) {
            $avatarFileChecks[$avatarPath] = $avatarDisk->exists($avatarPath);
        }

        $avatarHasFile = $avatarFileChecks[$avatarPath];
    }
@endphp

@if($avatarHasFile)
    <img src="{{ $avatarDisk->url($avatarPath) }}"
         alt="{{ $avatarUser->full_name ?? '' }}"
         title="{{ $avatarUser->full_name ?? '' }}"
         class="rounded-circle flex-shrink-0"
         style="width: {{ $avatarSize }}px; height: {{ $avatarSize }}px; object-fit: cover;">
@else
    <div class="rounded-circle bg-primary bg-opacity-10 text-primary flex-shrink-0
                d-inline-flex align-items-center justify-content-center"
         style="width: {{ $avatarSize }}px; height: {{ $avatarSize }}px;
                font-size: {{ round($avatarSize / 24, 2) }}rem; font-weight: 600;"
         title="{{ $avatarUser->full_name ?? '' }}">
        {{ mb_substr($avatarUser?->first_name ?? '', 0, 1) }}{{ mb_substr($avatarUser?->last_name ?? '', 0, 1) }}
    </div>
@endif
