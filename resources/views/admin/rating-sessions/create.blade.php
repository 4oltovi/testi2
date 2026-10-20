@extends('layouts.app')

@section('title', 'Рейтинги нав')
@section('page-header', 'Сохтани рейтинги нав')
@section('page-description', 'Фанҳо якбора интихоб мешаванд — санҷиши омодагӣ автоматӣ')

@section('content')
<form method="POST" action="{{ route('admin.rating-sessions.store') }}">
    @csrf
    <div class="row g-4">
        {{-- ==================== ТАНЗИМОТИ АСОСӢ ==================== --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Танзимоти асосӣ</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Номи рейтинг <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', 'Рейтинги 1 — семестри ҷорӣ') }}" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Давра <span class="text-danger">*</span></label>
                            <select name="period" class="form-select" required>
                                <option value="rating1">Рейтинги 1 (ҳафта 1-8)</option>
                                <option value="rating2">Рейтинги 2 (ҳафта 9-16)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Семестр <span class="text-danger">*</span></label>
                            <select name="semester_id" id="semester_id" class="form-select" required>
                                @foreach($semesters as $sem)
                                <option value="{{ $sem->id }}" @selected($sem->id == ($currentSemesterId ?? old('semester_id')))>
                                    {{ $sem->name }} — {{ $sem->academicYear?->name }}
                                    {{ $sem->is_current ? '(ҷорӣ)' : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Оғоз <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="start_at" id="start_at" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Анҷом <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="end_at" id="end_at" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label class="form-label">Вақти тест (дақ.)</label>
                            <input type="number" name="duration_minutes" class="form-control" value="45" min="5" max="180" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Шумораи саволҳо</label>
                            <input type="number" name="questions_count" class="form-control" value="30" min="5" max="100" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Кӯшишҳо</label>
                            <input type="number" name="max_attempts" class="form-control" value="2" min="1" max="5" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Режими вақт</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="schedule_mode" value="all" id="mode_all" checked onchange="toggleGroups(false)">
                            <label class="form-check-label" for="mode_all">Ҳама гурӯҳҳо якбора (равзанаи умумӣ)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="schedule_mode" value="by_group" id="mode_group" onchange="toggleGroups(true)">
                            <label class="form-check-label" for="mode_group">Аз рӯи гурӯҳҳо (ками нагрузка ба сервер)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== ФАНҲО + ГУРӮҲҲО ==================== --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Фанҳо <span class="text-danger">*</span></h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="checkAllVisible(true)">
                        <i class="bi bi-check-all me-1"></i>Ҳамаи фанҳои фаъол
                    </button>
                </div>
                <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                    <div id="subjectsAlert" class="alert alert-warning d-none mb-0 rounded-0 border-0"></div>
                    <table class="table table-sm table-hover mb-0">
                        <tbody id="subjectsTableBody">
                            @foreach($subjects as $subj)
                            <tr data-subject-id="{{ $subj->id }}">
                                <td class="ps-3">
                                    <div class="form-check">
                                        <input class="form-check-input subject-check" type="checkbox"
                                            name="subject_ids[]" value="{{ $subj->id }}" id="subj_{{ $subj->id }}">
                                        <label class="form-check-label" for="subj_{{ $subj->id }}">{{ $subj->name }}</label>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="subjectsEmpty" class="text-center text-muted py-3 d-none">Дар ин семестр фан нест</div>
                </div>
            </div>

            {{-- Ҷадвали гурӯҳҳо (режими by_group) --}}
            <div class="card border-0 shadow-sm d-none" id="groupsCard">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Равзанаи вақт барои гурӯҳҳо</h6>
                <div>
                    <small class="text-muted me-2">Агар холӣ бошад — равзанаи умумӣ истифода мешавад</small>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copySessionTimeToAll()">
                        <i class="bi bi-copy me-1"></i>Ҳамагӯҳ ба ҳамон вақт
                    </button>
                </div>
            </div>
                <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Гурӯҳ</th>
                                <th>Оғоз</th>
                                <th>Анҷом</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $g)
                            <tr>
                                <td class="ps-3">{{ $g->name }} <small class="text-muted">({{ $g->specialty?->name }})</small></td>
                                <td><input type="datetime-local" name="group_windows[{{ $g->id }}][start_at]" class="form-control form-control-sm gw-start"></td>
                                <td><input type="datetime-local" name="group_windows[{{ $g->id }}][end_at]" class="form-control form-control-sm gw-end"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-4">
        <a href="{{ route('admin.rating-sessions.index') }}" class="btn btn-outline-secondary me-2">Бозгашт</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> Сохтан ва санҷидани омодагӣ
        </button>
    </div>
</form>

<script>
    const subjectsBySemesterUrl = "{{ route('admin.rating-sessions.subjects-by-semester') }}";
    const csrfToken = "{{ csrf_token() }}";

    function checkAllVisible(state) {
        document.querySelectorAll('#subjectsTableBody .subject-check').forEach(c => c.checked = state);
    }

    function toggleGroups(show) {
        document.getElementById('groupsCard').classList.toggle('d-none', !show);
        if (show) {
            // Пешпур аз равзанаи умумӣ
            const s = document.getElementById('start_at').value;
            const e = document.getElementById('end_at').value;
            document.querySelectorAll('.gw-start').forEach(i => {
                if (!i.value) i.value = s;
            });
            document.querySelectorAll('.gw-end').forEach(i => {
                if (!i.value) i.value = e;
            });
        }
        loadSubjects();
    }

    function getSelectedGroupIds() {
        const modeByGroup = document.getElementById('mode_group')?.checked;
        if (!modeByGroup) {
            return [];
        }
        const groupIds = [];
        document.querySelectorAll('#groupsCard input[type="checkbox"].group-check:checked').forEach(cb => {
            groupIds.push(cb.value);
        });
        // If no group checkboxes with class, fallback to group_windows keys that have values? but groups are shown
        // better: look for inputs with name starting group_windows
        if (groupIds.length === 0) {
            document.querySelectorAll('#groupsCard input[name^="group_windows["]').forEach(input => {
                const match = input.name.match(/group_windows\[(\d+)\]/);
                if (match && !groupIds.includes(match[1])) {
                    // include if we want to consider group selected; easier: just collect all group ids from table rows
                }
            });
        }
        return groupIds;
    }

    function loadSubjects() {
        const semesterSelect = document.getElementById('semester_id');
        const semesterId = semesterSelect?.value;
        if (!semesterId) {
            return;
        }

        const modeByGroup = document.getElementById('mode_group')?.checked;
        const groupIds = [];
        if (modeByGroup) {
            // collect all groups shown
            document.querySelectorAll('#groupsCard tbody tr').forEach(row => {
                const input = row.querySelector('input[name^="group_windows["][name$="[start_at]"]');
                if (input) {
                    const match = input.name.match(/group_windows\[(\d+)\]/);
                    if (match) {
                        groupIds.push(match[1]);
                    }
                }
            });
        }

        const formData = new FormData();
        formData.append('semester_id', semesterId);
        formData.append('_token', csrfToken);
        if (groupIds.length > 0) {
            groupIds.forEach(id => formData.append('group_ids[]', id));
        }

        fetch(subjectsBySemesterUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            const allowed = new Set((data.subjects || []).map(s => String(s.id)));
            const tbody = document.getElementById('subjectsTableBody');
            const alertBox = document.getElementById('subjectsAlert');
            const emptyBox = document.getElementById('subjectsEmpty');
            let dropped = [];

            // remove unchecked subjects not in allowed? keep existing checked ones that remain
            document.querySelectorAll('#subjectsTableBody .subject-check').forEach(cb => {
                if (!allowed.has(String(cb.value))) {
                    if (cb.checked) {
                        dropped.push(cb.nextElementSibling?.textContent?.trim() || cb.value);
                        cb.checked = false;
                    }
                    const tr = cb.closest('tr');
                    if (tr) tr.remove();
                }
            });

            // add missing allowed subjects
            (data.subjects || []).forEach(subject => {
                if (!document.getElementById('subj_' + subject.id)) {
                    const tr = document.createElement('tr');
                    tr.setAttribute('data-subject-id', subject.id);
                    tr.innerHTML = '<td class="ps-3"><div class="form-check"><input class="form-check-input subject-check" type="checkbox" name="subject_ids[]" value="' + subject.id + '" id="subj_' + subject.id + '"><label class="form-check-label" for="subj_' + subject.id + '">' + subject.name.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</label></div></td>';
                    tbody.appendChild(tr);
                }
            });

            if (dropped.length > 0) {
                alertBox.textContent = 'Баъзе фанҳо аз рӯйхат хориҷ карда шуданд, зеро дар ин семестр/гурӯҳҳо вуҷуд надоранд: ' + dropped.slice(0, 5).join(', ') + (dropped.length > 5 ? ' ...' : '');
                alertBox.classList.remove('d-none');
            } else {
                alertBox.classList.add('d-none');
                alertBox.textContent = '';
            }

            if ((data.subjects || []).length === 0) {
                emptyBox.classList.remove('d-none');
            } else {
                emptyBox.classList.add('d-none');
            }
        })
        .catch(() => {
            // ignore
        });
    }

    function copySessionTimeToAll() {
        const s = document.getElementById('start_at').value;
        const e = document.getElementById('end_at').value;
        document.querySelectorAll('.gw-start').forEach(i => { i.value = s; });
        document.querySelectorAll('.gw-end').forEach(i => { i.value = e; });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const semesterSelect = document.getElementById('semester_id');
        if (semesterSelect) {
            semesterSelect.addEventListener('change', loadSubjects);
        }
        // reload when group mode changes or group windows change
        document.querySelectorAll('input[name="schedule_mode"]').forEach(r => {
            r.addEventListener('change', loadSubjects);
        });
        document.querySelectorAll('#groupsCard').forEach(card => {
            card.addEventListener('change', function(e) {
                if (e.target.name && e.target.name.indexOf('group_windows[') === 0) {
                    loadSubjects();
                }
            });
        });
        // initial load in case of old input
        loadSubjects();
    });
</script>
@endsection