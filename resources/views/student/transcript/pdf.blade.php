<!DOCTYPE html>
<html lang="tg">
<head>
    <meta charset="UTF-8">
    <title>Transcript — {{ $student?->user?->full_name ?? 'Донишҷӯ' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        h1 { text-align: center; }
        .summary { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Transcript</h1>
    <p><strong>Донишҷӯ:</strong> {{ $student?->user?->full_name ?? '-' }}</p>
    <p><strong>Гурӯҳ:</strong> {{ $student?->group?->name ?? '-' }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Фан</th>
                <th>Семестр</th>
                <th>Кредит</th>
                <th>Фоиз</th>
                <th>Баҳо</th>
                <th>GPA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grades as $index => $grade)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $grade->subject?->name ?? '—' }}</td>
                <td>{{ $grade->semester?->name ?? '—' }}</td>
                <td class="text-center">{{ $grade->credits_earned }}</td>
                <td class="text-center">{{ $grade->total_score ? number_format($grade->total_score, 0) . '%' : '—' }}</td>
                <td class="text-center">
                    @if($grade->letter_grade)
                        @php $g = \App\Enums\GradeScale::tryFrom($grade->letter_grade); @endphp
                        <span class="badge {{ $g?->badgeClass() ?? 'bg-secondary' }}">{{ $grade->letter_grade }}</span>
                    @else — @endif
                </td>
                <td class="text-center">{{ $grade->grade_point ? number_format($grade->grade_point, 2) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Маҷмӯъ:</strong></td>
                <td class="text-center"><strong>{{ $grades->sum('credits_earned') }}</strong></td>
                <td class="text-center"><strong>{{ number_format($grades->avg('total_score'), 0) }}%</strong></td>
                <td></td>
                <td class="text-center"><strong>{{ number_format($grades->avg('grade_point'), 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
