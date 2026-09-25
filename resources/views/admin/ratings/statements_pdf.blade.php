<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Ведомости рейтингӣ</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        .vedomost-container {
            page-break-after: always;
        }

        .vedomost-container:last-child {
            page-break-after: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-center {
            text-align: center;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        h5,
        h6 {
            margin: 5px 0;
        }

        .mt-4 {
            margin-top: 1.5rem;
        }

        .float-right {
            float: right;
        }

       @page {
            size: A4 portrait;
            margin: 15mm;
        }

        body {
            font-size: 12px;
        }

        .vedomost-container {
            page-break-after: always;
        }
        </style>
</head>

<body>
    @foreach($vedomosts as $vedomost)
    <div class="vedomost-container">
        <div class="text-center mb-3">
            @if($logo)
            <img src="{{ $logo }}" alt="Логотип" style="max-height: 80px;">
            @endif
            <h5>{!! nl2br(e($institutionName)) !!} </h5>
        </div>
        <div style="margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span><strong>Факултет:</strong> {{ $vedomost['group']->specialty?->department?->faculty?->name ?? '' }}</span>
                <span></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span><strong>Ихтисос:</strong> {{ $vedomost['group']->specialty?->name ?? '' }} ({{ $vedomost['subject']->credits ?? '' }})</span>
                <span><strong>Миқдори кредит:</strong> {{ $vedomost['subject']->credits ?? '' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span><strong>Гурӯҳ:</strong> {{ $vedomost['group']->name ?? '' }}</span>
                <span><strong>Нимсола:</strong> {{ $vedomost['semester']?->name ?? '' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span><strong>Соли хониш:</strong> {{ $vedomost['semester']?->academicYear?->name ?? '' }}</span>
                <span><strong>санаи имтиҳон:</strong> __________</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span><strong>Номи фан:</strong> {{ $vedomost['subject']?->name ?? '' }}</span>
                <span></span>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID</th>
                    <th>Ном ва насаб</th>
                    <th>Рейтинги 1 (R1)</th>
                    <th>Рейтинги 2 (R2)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vedomost['students'] as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student['student_id_number'] }}</td>
                    <td>{{ $student['full_name'] }}</td>
                    <td>{{ $student['rating1_score'] ?? '' }}</td>
                    <td>{{ $student['rating2_score'] ?? '' }}</td>

                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">
            <span><strong>Омӯзгор:</strong> {{ $vedomost['teacher']?->full_name }}</span>
            <span class="float-right"><strong>Имзо:</strong> ___________________</span>
        </div>
    </div>
    @endforeach
</body>

</html>