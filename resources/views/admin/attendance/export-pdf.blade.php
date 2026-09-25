<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Давомоти рузона</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { text-align: center; margin-bottom: 4px; font-size: 16px; }
        .info { text-align: center; margin-bottom: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .summary { margin-bottom: 12px; }
        .summary td { border: 1px solid #ccc; padding: 5px; text-align: center; }
        .present { color: #28a745; font-weight: bold; }
        .absent { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Давомоти рузона</h1>
    <div class="info">
        <p><strong>Сана:</strong> {{ $startDate }} — {{ $endDate }}</p>
    </div>

    <table class="summary">
        <tr>
            <td>Дар ҷамъ</td>
            <td>Ҳозир</td>
            <td>Ғоиб</td>
            <td>Фоиз</td>
        </tr>
        <tr>
            <td>{{ $summary['total'] }}</td>
            <td class="present">{{ $summary['present'] }}</td>
            <td class="absent">{{ $summary['absent'] }}</td>
            <td>{{ $summary['percentage'] }}%</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Сана</th>
                <th style="width: 38%;">Донишҷӣ</th>
                <th style="width: 20%;">Шиноса</th>
                <th style="width: 20%;">Гурūҳ</th>
                <th style="width: 10%;">Ҳолат</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $r)
            @php
                $statusClass = $r->status === 'present' ? 'present' : 'absent';
                $statusText = $r->status === 'present' ? 'Ҳозир' : 'Ғоиб';
            @endphp
            <tr>
                <td>{{ $r->attendance_date }}</td>
                <td>{{ $r->student_name }}</td>
                <td>{{ $r->student_id_number }}</td>
                <td>{{ $r->group_name }}</td>
                <td class="{{ $statusClass }}">{{ $statusText }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>