<!DOCTYPE html>
<html lang="tg">
<head>
    <meta charset="UTF-8">
    <title>Импорти саволномаи рейтинг</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; max-width: 900px; margin: 40px auto; }
        h1 { text-align: center; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #e0e0e0; }
        .required { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Импорти саволҳои рейтинг</h1>

    <div class="info">
        <h3>Шакли файл</h3>
        <p>Танҳо файлҳои <strong>CSV</strong> ё <strong>TXT</strong> бо ҷудокунаки <strong>вергул (,)</strong> қабул мешаванд.</p>
        <p>Сатри сарлавҳа бояд ин намуд бошад:</p>
        <code>question_text,options,correct,difficulty_level,explanation</code>
    </div>

    <div class="info">
        <h3>Миқдори сутунҳо</h3>
        <table>
            <thead>
                <tr>
                    <th>Сутун</th>
                    <th>Тавсиф</th>
                    <th>Мисол</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="required">question_text</td>
                    <td>Матни савол</td>
                    <td>Анатомияи инсон чист?</td>
                </tr>
                <tr>
                    <td class="required">options</td>
                    <td>4 вариант бо ҷудокунаки вергул (|)</td>
                    <td>Фан|Инсон|Сабз|Самт</td>
                </tr>
                <tr>
                    <td class="required">correct</td>
                    <td>Индекси варианти дуруст (0-3)</td>
                    <td>1</td>
                </tr>
                <tr>
                    <td>difficulty_level</td>
                    <td>Душворӣ: 1=Осон, 2=Миёна, 3=Душвор</td>
                    <td>2</td>
                </tr>
                <tr>
                    <td>explanation</td>
                    <td>Тавсифи савол (ихтиёрӣ)</td>
                    <td>Саволро барои санҷиши дониш дар сохта шуд.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="info">
        <h3>Мисоли сатри файл</h3>
        <pre>question_text,options,correct,difficulty_level,explanation
Анатомияи инсон чист?,Фан|Инсон|Сабз|Самт,1,2,Дарси аввали анатомия
Миёнаи овоз дар баландии чӣ аст?,Калин|Паст|Миёна|Харош,2,1,Барои санҷиши эхсос</pre>
    </div>

    <form method="POST" action="{{ route('admin.rating-questions.import') }}" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Фан <span class="text-danger">*</span></label>
            <select name="subject_id" class="form-select" required>
                <option value="">— Интихоб кунед —</option>
                @foreach($subjects as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Файли CSV <span class="text-danger">*</span></label>
            <input type="file" name="file" accept=".csv,.txt" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Импорт кардан</button>
        <a href="{{ route('admin.rating-questions.index') }}" class="btn btn-secondary">Баромад</a>
    </form>
</body>
</html>
