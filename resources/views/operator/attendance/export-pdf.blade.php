<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Давомоти рӯзона</title>
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
    <h1>Давомоти рӯзона</h1>
    <div class="info">
        <p><strong>Гурӯҳ:</strong> {{ $group->full_name }}</p>
        <p><strong>Сана:</strong> {{ $date }}</p>
        <p><strong>Сол/курс:</strong> {{ $group->course->name ?? '—' }} / {{ $group->specialty->name ?? '—' }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 35%;">Ному насаб</th>
                <th style="width: 25%;">Коди донишҷӯ</th>
                <th style="width: 20%;">Статус</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
                @php
                    $status = $attendanceData[$student->id] ?? 'present';
                    $statusText = $status === 'present' ? 'Ҳозир' : 'Ғоиб';
                    $statusClass = $status === 'present' ? 'present' : 'absent';
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->user->last_name }} {{ $student->user->first_name }}</td>
                    <td>{{ $student->student_id ?? '—' }}</td>
                    <td class="{{ $statusClass }}">{{ $statusText }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
