<!DOCTYPE html>
<html lang="tg">

<head>
    <meta charset="UTF-8">
    <title>Қарздориҳо</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 7mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        h3 {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 7px 0;
            padding: 0;
        }

        h4 {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0 5px 0;
            padding: 4px 6px;
            background: #e8e8e8;
            border: 1px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0 0 10px 0;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px 4px;
            font-size: 11px;
            line-height: 1.15;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        th {
            background: #e8e8e8;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            padding: 6px 3px;
        }

        td {
            text-align: left;
        }

        .text-center {
            text-align: center !important;
        }

        th:nth-child(1),
        td:nth-child(1) {
            width: 4%;
            text-align: center;
        }

        th:nth-child(2),
        td:nth-child(2) {
            width: 14%;
        }

        th:nth-child(3),
        td:nth-child(3) {
            width: 15%;
        }

        th:nth-child(4),
        td:nth-child(4) {
            width: 12%;
        }

        th:nth-child(5),
        td:nth-child(5) {
            width: 13%;
        }

        th:nth-child(6),
        td:nth-child(6) {
            width: 13%;
        }

        th:nth-child(7),
        td:nth-child(7) {
            width: 12%;
            text-align: center;
        }

        th:nth-child(8),
        td:nth-child(8) {
            width: 8%;
            text-align: center;
        }

        th:nth-child(9),
        td:nth-child(9) {
            width: 9%;
        }

        tr {
            page-break-inside: avoid;
        }

        tbody tr {
            height: 28px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            table {
                width: 100%;
            }

            th,
            td {
                border: 1px solid #000;
            }

            h3 {
                margin-bottom: 7px;
            }
        }
    </style>
</head>

<body>
    <h3>Қарздориҳо</h3>
    @foreach($groupedDebts as $groupName => $debts)
    <h4>Гурӯҳ: {{ $groupName }}</h4>
    <table>
        <thead>
            <tr>
                <th class="text-center">№</th>
                <th>Гурӯҳ</th>
                <th>Донишҷӯ</th>
                <th>Фан</th>
                <th>Сабаб</th>
                <th>Баҳо</th>
                <th>Санаи қарз</th>
                <th>Кӯшиш</th>
                <th>Ҳолат</th>
            </tr>
        </thead>
        <tbody>
            @foreach($debts as $index => $debt)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $debt->student?->group?->name ?? '—' }}</td>
                <td>{{ $debt->student?->user?->short_name ?? '—' }}</td>
                <td><small>{{ $debt->subject?->name ?? '—' }}</small></td>
                <td><small>{{ $debt->reason_label ?? '—' }}</small></td>
                <td>{{ $debt->original_grade ?? '—' }} ({{ $debt->original_score ?? '—' }}%)</td>
                <td><small>{{ $debt->debt_date?->format('d.m.Y') ?? '—' }}</small></td>
                <td>{{ $debt->retake_attempts_used ?? 0 }}/{{ $debt->max_retake_attempts ?? 3 }}</td>
                <td><span class="badge {{ $debt->status?->badgeClass() }}">{{ $debt->status?->label() }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach
</body>

</html>
