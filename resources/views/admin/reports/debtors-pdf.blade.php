<!DOCTYPE html>
<html lang="tg">
<head>
    <meta charset="UTF-8">
    <title>Рӯйхати қарздорон</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
        h1 { text-align: center; }
    </style>
</head>
<body>
    <h1>Рӯйхати донишҷӯёни қарздор</h1>
    <table>
        <thead>
            <tr>
                <th>№</th>
                <th>Донишҷӯ</th>
                <th>Гурӯҳ</th>
                <th>Фан</th>
                <th>Семестр</th>
                <th>Сабаб</th>
                <th>Баҳо</th>
                <th>Санаи қарз</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debts as $i => $debt)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $debt->student?->user?->full_name ?? '-' }}</td>
                    <td>{{ $debt->student?->group?->name ?? '-' }}</td>
                    <td>{{ $debt->subject?->name ?? '-' }}</td>
                    <td>{{ $debt->semester?->name ?? '-' }}</td>
                    <td>{{ $debt->reason ?? '-' }}</td>
                    <td>{{ $debt->original_grade ?? '-' }}</td>
                    <td>{{ $debt->debt_date?->format('Y-m-d') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>