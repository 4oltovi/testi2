@php
    // Тақсимоти дарсҳо/соатҳо аз рӯи навъ — як сатр = як фан,
    // навъҳо ҳамчун хурд-бейҷ нишон дода мешаванд
    $breakdown = $assignment->lessonTypeBreakdown();
    $isHours = $assignment->lecture_hours !== null
        || $assignment->practical_hours !== null
        || $assignment->lab_hours !== null;
    $unit = $isHours ? ' соат' : ' дарс';
    $totalLessons = array_sum($breakdown);
    $typeLabels = ['lecture' => 'Лексия', 'practice' => 'Амалӣ', 'lab' => 'Лабораторӣ'];
    $typeColors = ['lecture' => 'primary', 'practice' => 'success', 'lab' => 'info'];
@endphp
@if($totalLessons > 0)
    @foreach($breakdown as $type => $count)
        @if($count > 0)
            <span class="badge bg-{{ $typeColors[$type] }}">{{ $typeLabels[$type] }}: {{ $count }}{{ $unit }}</span>
        @endif
    @endforeach
@else
    {{ match($assignment->lesson_type) {
        'lecture' => 'Лексия',
        'practice' => 'Амалӣ',
        'lab' => 'Лабораторӣ',
        default => $assignment->lesson_type ?? '—',
    } }}
@endif
