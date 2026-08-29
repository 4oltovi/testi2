<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ведомости такрорӣ</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #333; padding: 6px; text-align: center; }
        th { background-color: #f0f0f0; }
        .group-header { background-color: #e0e0e0; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .info { margin-bottom: 15px; }
        .info td { border: none; text-align: left; padding: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>ВЕДОМОСТИ ТАКРОРӢ</h2>
        <p>{{ $exam->subject->name ?? 'Фан' }} | {{ $exam->exam_date?->format('d.m.Y') ?? '' }}</p>
    </div>

    <table class="info">
        <tr>
            <td><strong>Фан:</strong> {{ $exam->subject->name ?? '-' }}</td>
            <td><strong>Семестр:</strong> {{ $exam->semester->name ?? '-' }}</td>
            <td><strong>Имтиҳон:</strong> {{ $exam->title }}</td>
        </tr>
    </table>

    @foreach($groupedRows as $groupName => $groupRows)
    <div style="margin-bottom: 20px;">
        <table class="group-header">
            <tr>
                <th colspan="9">{{ $groupName }}</th>
            </tr>
            <tr>
                <th style="width: 40px;">№</th>
                <th>ID</th>
                <th>Номи донишҷӯ</th>
                <th>Баҳои аслӣ</th>
                <th>Холи аслӣ</th>
                <th>Кӯшиш</th>
                <th>Баҳои такрорӣ</th>
                <th>Холи такрорӣ</th>
                <th>Ҳолат</th>
            </tr>
        </table>
        <table>
            @foreach($groupRows as $row)
            <tr>
                <td>{{ $row['n'] }}</td>
                <td>{{ $row['student_id'] }}</td>
                <td style="text-align: left;">{{ $row['fio'] }}</td>
                <td>{{ $row['original_score'] }}</td>
                <td>{{ $row['original_grade'] }}</td>
                <td>{{ $row['attempt'] }}</td>
                <td>{{ $row['retake_score'] }}</td>
                <td>{{ $row['retake_grade'] }}</td>
                <td>{{ $row['status'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endforeach

    <div style="margin-top: 30px;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; border: none;">Имзои масъул: ___________________</td>
                <td style="width: 50%; border: none;">Сана: {{ date('d.m.Y') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
