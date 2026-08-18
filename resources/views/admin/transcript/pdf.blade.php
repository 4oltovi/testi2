<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Транскрипт</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #000;
        }

        .center {
            text-align: center;
        }

        table {
            border-collapse: collapse;
        }

        table.main {
            width: 100%;
            margin-top: 8px;
        }

        table.main th,
        table.main td {
            border: 1px solid #000;
            padding: 2px 2px;
            font-size: 7px;
        }

        table.main th {
            text-align: center;
            background: #f0f0f0;
        }

        .c {
            text-align: center;
        }

        table.sum {
            margin-top: 10px;
            margin-left: auto;
        }

        table.sum th,
        table.sum td {
            border: 1px solid #000;
            padding: 3px 6px;
            font-size: 8px;
        }

        table.sum th {
            background: #f0f0f0;
            text-align: center;
        }

        .totals {
            margin-top: 8px;
            text-align: right;
        }

        .totals div {
            margin-bottom: 4px;
        }

        .sign {
            margin-top: 25px;
        }

        .sign div {
            margin-bottom: 14px;
        }
    </style>
</head>

<body>

    {{-- Сарлавҳа: логотип дар мобайн --}}
    <div class="center">
        @php
        $logoPath = \App\Models\Setting::get('institution_logo', 'images/logo.png');
        $w = 0; $h = 0;
        if ($logoPath && file_exists(public_path($logoPath))) {
        $size = @getimagesize(public_path($logoPath));
        if ($size && $size[0] > 0 && $size[1] > 0) {
        $k = min(80 / $size[0], 80 / $size[1], 1);
        $w = (int) round($size[0] * $k);
        $h = (int) round($size[1] * $k);
        }
        }
        @endphp
        @if($w > 0)
        <img src="{{ public_path($logoPath) }}" style="width:{{ $w }}px; height:{{ $h }}px;">
        @endif
        <div><b>{{ $institutionName }}</b></div>
        <div style="margin-top:6px;"><b>Т Р А Н С К Р И П Т</b> (Маълумотномаи академӣ)</div>
        <div>№ {{ $transcriptNumber ?? '________' }} аз "{{ $date->format('d.m.Y') }}"</div>
    </div>

    {{-- Маълумоти донишҷӯ: аз ду канор, бе фосилаи зиёд --}}
    <table style="width:100%; border:none; border-collapse:collapse; margin-top:6px;">
        <tr>
            <td style="border:none; vertical-align:top; width:50%;">
                <table style="border:none; border-collapse:collapse;">
                    <tr>
                        <td style="border:none; padding:2px 6px 2px 0;">Ному насаб:</td>
                        <td style="border:none; padding:2px 0;"><b>{{ $student->user?->full_name ?? '-' }}</b></td>
                    </tr>
                    <tr>
                        <td style="border:none; padding:2px 6px 2px 0;">Ихтисос:</td>
                        <td style="border:none; padding:2px 0;">{{ $specialtyCode }} "{{ $student->specialty?->name ?? '-' }}"</td>
                    </tr>
                    <tr>
                        <td style="border:none; padding:2px 6px 2px 0;">Факулта:</td>
                        <td style="border:none; padding:2px 0;">{{ $facultyName }}</td>
                    </tr>
                </table>
            </td>
            <td style="border:none; vertical-align:top; width:50%;">
                <table style="border:none; border-collapse:collapse; margin-left:auto;">
                    <tr>
                        <td style="border:none; padding:2px 6px 2px 0;">ID донишҷӯ:</td>
                        <td style="border:none; padding:2px 0;"><b>{{ $student->student_id_number ?? $student->id }}</b></td>
                    </tr>
                    <tr>
                        <td style="border:none; padding:2px 6px 2px 0;">Бахш - Гуруҳ:</td>
                        <td style="border:none; padding:2px 0;"><b>{{ $bahshGroup }}</b></td>
                    </tr>
                    <tr>
                        <td style="border:none; padding:2px 6px 2px 0;">Шӯъба:</td>
                        <td style="border:none; padding:2px 0;">{{ $studyForm }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Ҷадвали асосӣ: 15 сутун --}}
    <table class="main">
        <thead>
            <tr>
                <th>Бахш</th>
                <th>Сем</th>
                <th>Гуруҳ</th>
                <th>Фан</th>
                <th>Р1</th>
                <th>Р2</th>
                <th>Имтиҳон</th>
                <th>Ҳамагӣ</th>
                <th>Баҳо</th>
                <th>Баҳ. анъ.</th>
                <th>иф. ад.</th>
                <th>Кредит</th>
                <th>Кр. азх.</th>
                <th>Балл</th>
                <th>Компонент</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
            <tr>
                <td class="c">{{ $r['course'] }}</td>
                <td class="c">{{ $r['sem'] }}</td>
                <td class="c">{{ $r['group'] }}</td>
                <td>{{ $r['subject'] }}</td>
                <td class="c">{{ $r['r1'] }}</td>
                <td class="c">{{ $r['r2'] }}</td>
                <td class="c">{{ $r['exam'] }}</td>
                <td class="c">{{ $r['total'] }}</td>
                <td class="c">{{ $r['letter'] }}</td>
                <td class="c">{{ $r['trad'] }}</td>
                <td class="c">{{ $r['point'] }}</td>
                <td class="c">{{ $r['credits'] }}</td>
                <td class="c">{{ $r['earned'] }}</td>
                <td class="c">{{ $r['ball'] }}</td>
                <td class="c">{{ $r['component'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Хулоса по семестрҳо --}}
    <table class="sum">
        <thead>
            <tr>
                <th>Сем.</th>
                <th>Балл</th>
                <th>Кредит</th>
                <th>Кред. азхуд.</th>
                <th>GPA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $s)
            <tr>
                <td class="c">{{ $s['sem'] }}</td>
                <td class="c">{{ $s['ball'] }}</td>
                <td class="c">{{ $s['credits'] }}</td>
                <td class="c">{{ $s['earned'] }}</td>
                <td class="c">{{ $s['gpa'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><b>Ҳамагӣ кредитҳо азхуд карда шуданд: {{ $totalEarned }}</b></div>
        <div><b>Аз он ҷумла: Ҳатмӣ: {{ $totalMandatory }}</b></div>
    </div>

    {{-- Имзоҳо --}}
    <div class="sign">
        <div>Директор (ё муовини директор оид ба таълим): _________________ {{ $deputyDirector }}</div>
        <div>Сардори маркази тестӣ: _________________ {{ $centerHead }}</div>
    </div>

</body>

</html>