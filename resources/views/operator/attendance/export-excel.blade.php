<table>
    <thead>
        <tr>
            <th>№</th>
            <th>Ному насаб</th>
            <th>Коди донишҷӯ</th>
            <th>Статус</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $index => $student)
            @php
                $status = $attendanceData[$student->id] ?? 'present';
                $statusText = $status === 'present' ? 'Ҳозир' : 'Ғоиб';
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->user->last_name }} {{ $student->user->first_name }}</td>
                <td>{{ $student->student_id ?? '—' }}</td>
                <td>{{ $statusText }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
