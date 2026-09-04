<table>
    <thead>
        <tr>
            <th>№</th>
            <th>Ф.И.О.</th>
            <th>Гурӯҳ</th>
            <th>Сана</th>
            <th>Статус</th>
            <th>Соатҳои ғоибӣ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $index => $record)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $record->student_name ?? '—' }}</td>
                <td>{{ $record->group_name ?? '—' }}</td>
                <td>{{ \Carbon\Carbon::parse($record->attendance_date)->format('d.m.Y') }}</td>
                <td>{{ $record->status === 'absent' ? 'Ғоиб (Н/Б)' : 'Ҳозир (+)' }}</td>
                <td>{{ $record->status === 'absent' ? '6 соат' : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
