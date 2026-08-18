<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ведомост</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .header div {
            margin-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px 6px;
        }

        th {
            text-align: center;
            background: #f0f0f0;
        }

        td.c {
            text-align: center;
        }

        .sign {
            margin-top: 30px;
        }

        .sign div {
            margin-bottom: 14px;
        }

        .page {
            margin-top: 10px;
            text-align: right;
            font-size: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="header">
            {{-- Логотип дар мобайн + номи муассиса зераш --}}
            <div style="text-align:center; margin-bottom:8px;">
                @php
                $logoPath = \App\Models\Setting::get('institution_logo', 'images/logo.png');
                $w = 0; $h = 0;
                if ($logoPath && file_exists(public_path($logoPath))) {
                $size = @getimagesize(public_path($logoPath));
                if ($size && $size[0] > 0 && $size[1] > 0) {
                $maxW = 100;
                $maxH = 100;
                $k = min($maxW / $size[0], $maxH / $size[1], 1);
                $w = (int) round($size[0] * $k);
                $h = (int) round($size[1] * $k);
                }
                }
                @endphp
                @if($w > 0)
                <img src="{{ public_path($logoPath) }}" style="width:{{ $w }}px; height:{{ $h }}px;">
                @endif
                <div><b>{{ $institutionName ?? 'Муассисаи ғайридавлатии коллеҷи тиббии "Даво" Маркази тестӣ' }}</b></div>
            </div>

            {{-- Маълумотҳо аз ду тараф --}}
            <table style="width:100%; border:none; border-collapse:collapse;">
                <tr>
                    <td style="border:none; padding:2px 0;">Факултет: {{ $v->group?->specialty?->department?->faculty?->name ?? '-' }}</td>
                    <td style="border:none; padding:2px 0; text-align:right;"></td>
                </tr>
                <tr>
                    <td style="border:none; padding:2px 0;">Ихтисос: {{ optional(optional($v->group)->specialty)->name ?? '-' }}</td>
                    <td style="border:none; padding:2px 0; text-align:right;">Миқдори кредит: <b>{{ $v->subjectAssignment?->credits ?? $v->subject?->credits }}</b></td>
                </tr>
                <tr>
                    <td style="border:none; padding:2px 0;">Гурӯҳ: <b>{{ optional($v->group)->name ?? '-' }}</b></td>
                    <td style="border:none; padding:2px 0; text-align:right;">Нимсола: <b>{{ optional($v->semester)->name ?? optional($v->semester)->number ?? $v->semester_id }}</b></td>
                </tr>
                <tr>
                    <td style="border:none; padding:2px 0;">Соли хониш: <b>{{ optional($v->academicYear)->name ?? (optional($v->academicYear)->start_year ? optional($v->academicYear)->start_year . '-' . (optional($v->academicYear)->start_year + 1) : '-') }}</b></td>
                    <td style="border:none; padding:2px 0; text-align:right;">санаи имтиҳон: <b>{{ ($examDate ?? $v->exam_date)?->format('d.m.Y') ?? '__________' }}</b></td>
                </tr>
                <tr>
                    <td style="border:none; padding:2px 0;" colspan="2">Номи фан: <b>{{ optional($v->subject)->name ?? '-' }}</b></td>
                </tr>
            </table>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:4%">№</th>
                    <th style="width:12%">Рақами ID</th>
                    <th style="width:30%">Ному насаб</th>
                    <th>Р1</th>
                    <th>Р2</th>
                    <th>Иҷ</th>
                    <th>Бҷф</th>
                    <th>Эҳ</th>
                    <th>ЭА</th>
                    <th>ЭАА</th>
                    <th>Бал</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td class="c">{{ $r['n'] }}</td>
                    <td class="c">{{ $r['code'] }}</td>
                    <td>{{ $r['fio'] }}</td>
                    <td class="c">{{ $r['r1'] }}</td>
                    <td class="c">{{ $r['r2'] }}</td>
                    <td class="c">{{ $r['examComp'] }}</td>
                    <td class="c">{{ $r['total'] }}</td>
                    <td class="c">{{ $r['letter'] }}</td>
                    <td class="c">{{ $r['point'] }}</td>
                    <td class="c">{{ $r['cred'] }}</td>
                    <td class="c">{{ $r['ball'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="sign">
            <div>Муовини директор оид ба корҳои таълимӣ _________________ {{ $deputyDirector ?? 'Гулов М.' }}</div>
            <div>Сардори маркази машварат ва бақайдгирӣ, маркази тестӣ _________________ {{ $centerHead ?? 'Хоҷаев М.М.' }}</div>
        </div>

        <div class="page">Саҳифа 1/1</div>

</body>

</html>