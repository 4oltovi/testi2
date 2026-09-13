@extends('layouts.app')

@section('title', 'Аудитория')
@section('page-header', 'Аудитория')
@section('page-description', 'Маълумоти аудитория')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <strong>Рақами аудитория</strong>
                        <p>{{ $classroom->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Бино</strong>
                        <p>{{ $classroom->building ?? '—' }}</p>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <strong>Ошёна</strong>
                        <p>{{ $classroom->floor ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <strong>Ҷойгоҳ</strong>
                        <p>{{ $classroom->capacity ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <strong>Навъ</strong>
                        <p>{{ $classroom->type ?? '—' }}</p>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <strong>Проектор</strong>
                        <p>{{ $classroom->has_projector ? 'Аст' : 'Надорад' }}</p>
                    </div>
                    <div class="col-md-4">
                        <strong>Компютер</strong>
                        <p>{{ $classroom->has_computers ? 'Аст' : 'Надорад' }}</p>
                    </div>
                    <div class="col-md-4">
                        <strong>Фаъол</strong>
                        <p>{{ $classroom->is_active ? 'Аст' : 'Не' }}</p>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.structure.classrooms.edit', $classroom) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Таҳрир
                    </a>
                    <a href="{{ route('admin.structure.classrooms.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Бозгашт
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
