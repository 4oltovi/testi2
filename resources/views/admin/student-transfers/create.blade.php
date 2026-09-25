@extends('layouts.app')

@section('title', 'Гузариши донишҷӯ — нав')
@section('page-header', 'Гузариши донишҷӯ')
@section('page-description', 'Гузаронидани донишҷӯ ба гурӯҳ/ихтисоси нав')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Гузариши донишҷӯ</h6>
            </div>
            <div class="card-body">
                @if($student)
                <div class="alert alert-info mb-4">
                    <strong>Донишҷӯ:</strong> {{ $student->user?->short_name ?? 'Донишҷӯ #' . $student->id }} |
                    <strong>Гурӯҳи ҳозира:</strong> {{ $student->group?->name ?? '-' }} |
                    <strong>Ихтисоси ҳозира:</strong> {{ $student->specialty?->name ?? '-' }}
                </div>
                @endif

                <form method="POST" action="{{ route('admin.student-transfers.store') }}">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $student?->id ?? old('student_id') }}">

                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Донишҷӯ <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-select" required {{ $student ? 'disabled' : '' }}>
                                <option value="">Интихоб кунед...</option>
                                @foreach(\App\Models\Student::with('user')->get() as $s)
                                <option value="{{ $s->id }}" {{ old('student_id', $student?->id) == $s->id ? 'selected' : '' }}>
                                    {{ $s->user?->short_name ?? 'Донишҷӯ #' . $s->id }} ({{ $s->group?->name ?? '-' }})
                                </option>
                                @endforeach
                            </select>
                            @if($student)
                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Гурӯҳи қаблӣ <span class="text-danger">*</span></label>
                            <select name="from_group_id" class="form-select" required>
                                <option value="">Интихоб кунед...</option>
                                @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ old('from_group_id', $student?->group_id) == $group->id ? 'selected' : '' }}>
                                    {{ $group->full_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Гурӯҳи нав <span class="text-danger">*</span></label>
                            <select name="to_group_id" class="form-select" required>
                                <option value="">Интихоб кунед...</option>
                                @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ old('to_group_id') == $group->id ? 'selected' : '' }}>
                                    {{ $group->full_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ихтисоси қаблӣ <span class="text-danger">*</span></label>
                            <select name="from_specialty_id" class="form-select" required>
                                <option value="">Интихоб кунед...</option>
                                @foreach($specialties as $specialty)
                                <option value="{{ $specialty->id }}" {{ old('from_specialty_id', $student?->specialty_id) == $specialty->id ? 'selected' : '' }}>
                                    {{ $specialty->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ихтисоси нав <span class="text-danger">*</span></label>
                            <select name="to_specialty_id" class="form-select" required>
                                <option value="">Интихоб кунед...</option>
                                @foreach($specialties as $specialty)
                                <option value="{{ $specialty->id }}" {{ old('to_specialty_id') == $specialty->id ? 'selected' : '' }}>
                                    {{ $specialty->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Санаи гузариш <span class="text-danger">*</span></label>
                            <input type="date" name="transfer_date" class="form-control" required value="{{ old('transfer_date', date('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Рақами фармон</label>
                            <input type="text" name="order_number" class="form-control" value="{{ old('order_number') }}" placeholder="Масалан: №123/2026">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Сабаб</label>
                            <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="Сабаби гузариш">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Тавзеҳот</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Маълумоти иловагӣ...">{{ old('note') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('admin.student-transfers.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Бозгашт
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Гузаришро сабт кардан
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
