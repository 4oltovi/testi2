<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Протоколи рейтинг</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
        }

        .head {
            text-align: center;
            margin-bottom: 10px;
        }

        .head h3 {
            margin: 4px 0;
            font-size: 13px;
        }

        .info {
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 4px 6px;
        }

        th {
            background: #eee;
        }

        .sign {
            margin-top: 30px;
        }

        .sign td {
            border: none;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <div class="head">
        <div>{{ \App\Models\Setting::get('institution_name', 'Муассисаи таълимӣ') }}</div>
        <h3>ПРОТОКОЛИ натиҷаҳои рейтинги онлайн</h3>
        <div>{{ $session->name }} ({{ $session->period === 'rating1' ? 'Рейтинги 1' : 'Рейтинги 2' }})</div>
    </div>

    <div class="info">
        Семестр: {{ $session->semester?->name }} — {{ $session->semester?->academicYear?->name }}<br>
        Давраи супориш: {{ $session->start?->format('d.m.Y H:i') ?? '—' }} — {{ $session->end?->format('d.m.Y H:i') ?? '—' }}<br>
        Журнал: {{ $results['journal_max'] }} бал + Тест: {{ $results['test_max'] }} бал = 100 бал<br>
        Санаи барориш: {{ now()->format('d.m.Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">№</th>
                <th style="width:26%">Донишҷӯ</th>
                <th style="width:12%">Гурӯҳ</th>
                <th style="width:26%">Фан</th>
                <th style="width:8%">Кӯшиш</th>
                <th style="width:10%">Тест (%)</th>
                <th style="width:14%">Балл (аз {{ $results['test_max'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results['rows'] as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row['student'] }}</td>
                <td>{{ $row['group'] }}</td>
                <td>{{ $row['subject'] }}</td>
                <td>{{ $row['attempts'] ?? '—' }}</td>
                <td>{{ $row['pct'] !== null ? $row['pct'] . '%' : '—' }}</td>
                <td>{{ $row['scaled'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td>Мудири кафедра: ____________________</td>
            <td>Декан: ____________________</td>
            <td>Мудири маркази тестӣ: ____________________</td>
        </tr>
    </table>
</body>

</html>