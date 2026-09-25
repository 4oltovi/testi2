@extends('layouts.app')

@section('title', 'Ведомости рейтингӣ')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ведомости рейтингӣ</h3>
                <p class="card-category">Интихоб кунед, то ведомостҳоро бубинед</p>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.ratings.statements') }}">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="semester_id">Семестр</label>
                                <select name="semester_id" id="semester_id" class="form-control" required>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}" {{ $semesterId == $semester->id ? 'selected' : '' }}>
                                            {{ $semester->name }} ({{ $semester->academicYear?->name }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="group_id">Гурӯҳ</label>
                                <select name="group_id" id="group_id" class="form-control" required>
                                    <option value="">Гурӯҳро интихоб кунед</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ $selectedGroupId == $group->id ? 'selected' : '' }}>
                                            {{ $group->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 align-self-end">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Нишон додан</button>
                                @if(!empty($vedomosts))
                                    <a href="{{ route('admin.ratings.statements', array_merge(request()->query(), ['download' => 'pdf'])) }}" class="btn btn-success">
                                        <i class="fas fa-download"></i> Боргирӣ (PDF)
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>

                @if(!empty($vedomosts))
                    @foreach($vedomosts as $vedomost)
                        <div class="vedomost-container mb-5 mt-4" style="page-break-after: always;">
                            <div class="text-center mb-4">
                                @if($logo)
                                    <img src="{{ asset('storage/' . $logo) }}" alt="Логотип" style="max-height: 80px;">
                                @endif
                                <h5>{!! nl2br(e($institutionName ?? 'Номи муассиса')) !!} </h5>
                                <h3>Ведомости рейтингӣ</h3>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><strong>Фан:</strong> {{ $vedomost['subject']->name }}</span>
                                <span><strong>Гурӯҳ:</strong> {{ $vedomost['group']->name }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span><strong>Семестр:</strong> {{ $vedomost['semester']->name }}</span>
                                <span><strong>Сана:</strong> {{ now()->format('d.m.Y') }}</span>
                            </div>

                            <table class="table table-bordered table-striped">
                                <thead class="thead-light">
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
                                <span style="float: right;"><strong>Имзо:</strong> ___________________</span>
                            </div>
                        </div>
                    @endforeach
                @elseif(request()->has('group_id'))
                    <div class="alert alert-info mt-4">Маълумот барои гурӯҳ ва семестри интихобшуда ёфт нашуд.</div>
                @endif
            </div>
        </div>
    </div>
@endsection