<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ҳисоботи умумӣ</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { text-align: center; margin-bottom: 5px; }
        .info { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .present { color: #28a745; font-weight: bold; }
        .absent { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Ҳисоботи умумии давомот</h1>
    <div class="info">
        <p><strong>Аз сана:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d.m.Y') }}</p>
        <p><strong>То сана:</strong> {{ \Carbon\Carbon::parse($endDate)->format('d.m.Y') }}</p>
        <p><strong>Ҳамагӣ сабтҳо:</strong> {{ $records->count() }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 30%;">Ф.И.О.</th>
                <th style="width: 15%;">Гурӯҳ</th>
                <th style="width: 12%;">Сана</th>
                <th style="width: 18%;">Статус</th>
                <th style="width: 20%;">Соатҳои ғоибӣ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $index => $record)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $record->student_name ?? '—' }}</td>
                    <td>{{ $record->group_name ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->attendance_date)->format('d.m.Y') }}</td>
                    <td class="{{ $record->status === 'absent' ? 'absent' : 'present' }}">
                        {{ $record->status === 'absent' ? 'Ғоиб (Н/Б)' : 'Ҳозир (+)' }}
                    </td>
                    <td>{{ $record->status === 'absent' ? '6 соат' : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
