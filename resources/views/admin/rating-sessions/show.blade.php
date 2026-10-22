@extends('layouts.app')

@section('title', $session->name)
@section('page-header', $session->name)
@section('page-description',
($session->period === 'rating1' ? 'Рейтинги 1' : 'Рейтинги 2') .
' | ' . ($session->semester?->name ?? '-') . ' — ' . ($session->semester?->academicYear?->name ?? ''))

@section('content')
{{-- ==================== МАЪЛУМОТ + АМАЛҲО ==================== --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col">
                        <h5 class="mb-0">{{ $session->start_at?->format('d.m.Y H:i') ?? '—' }}</h5>
                        <small class="text-muted">Оғоз</small>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">{{ $session->end_at?->format('d.m.Y H:i') ?? '—' }}</h5>
                        <small class="text-muted">Анҷом</small>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">{{ $session->duration_minutes }}</h5>
                        <small class="text-muted">Дақиқа</small>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">{{ $session->questions_count }}</h5>
                        <small class="text-muted">Саволҳо</small>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">{{ $session->max_attempts }}</h5>
                        <small class="text-muted">Кӯшишҳо</small>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">{{ $session->schedule_mode === 'by_group' ? 'Аз рӯи гурӯҳ' : 'Умумӣ' }}</h5>
                        <small class="text-muted">Режим</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-grid gap-2">
                @if($session->status === 'draft')
                <form method="POST" action="{{ route('admin.rating-sessions.publish', $session->id) }}">
                    @csrf
                    <button class="btn btn-success w-100" {{ $readiness['missing'] > 0 ? 'disabled' : '' }}>
                        <i class="bi bi-broadcast me-1"></i> Нашр кардан
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.rating-sessions.destroy', $session->id) }}"
                    onsubmit="return confirm('Сессия нест шавад?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger w-100"><i class="bi bi-trash me-1"></i> Нест кардан</button>
                </form>
                @else
                <a href="{{ route('admin.rating-sessions.protocol', $session->id) }}" class="btn btn-outline-dark w-100">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Протоколи PDF
                </a>
                <form method="POST" action="{{ route('admin.rating-sessions.extend', $session->id) }}" class="d-flex gap-2">
                    @csrf
                    <input type="number" name="hours" class="form-control" value="24" min="1" max="168">
                    <button class="btn btn-outline-warning text-nowrap"><i class="bi bi-clock-history me-1"></i>Дароз кардан</button>
                </form>
                <form method="POST" action="{{ route('admin.rating-sessions.close', $session->id) }}"
                    onsubmit="return confirm('Рейтинг пӯшида шавад?')">
                    @csrf
                    <button class="btn btn-danger w-100"><i class="bi bi-lock me-1"></i> Пӯшидан</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ==================== САНҶИШИ ОМАДАГӢ ==================== --}}
<div class="card border-0 shadow-sm mb-4 {{ $readiness['missing'] > 0 ? 'border-danger border-2' : 'border-success border-2' }}">
    <div class="card-body">
        <h6 class="mb-2">
            <i class="bi bi-clipboard-check me-2"></i>Санҷиши омодагии саволҳо:
            <span class="badge {{ $readiness['missing'] > 0 ? 'bg-danger' : 'bg-success' }}">
                {{ $readiness['ready'] }} / {{ $readiness['total'] }} фан тайёр
            </span>
        </h6>
        @if($readiness['missing'] > 0)
        <small class="text-danger">
            Саволҳо кам аст (ҳадди ақал {{ $session->questions_count }} лозим) барои:
            @foreach($session->subjects->whereIn('id', $readiness['missing_ids']) as $m)
            <span class="badge bg-danger-subtle text-danger">{{ $m->name }}</span>
            @endforeach
            — ба <a href="{{ route('admin.rating-questions.index') }}">Саволномаҳои рейтинг</a> гузаред.
        </small>
        @else
        <small class="text-success">Ҳамаи фанҳо саволҳои кофӣ доранд — нашр имкон аст ✅</small>
        @endif
    </div>
</div>

{{-- ==================== НАТИҶАҲО ==================== --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i>Натиҷаҳо (автоматӣ)</h6>
        <small class="text-muted">Журнал: {{ $results['journal_max'] }} бал | Тест: {{ $results['test_max'] }} бал</small>
    </div>
    <div class="card-body border-bottom py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="rsSearch" class="form-control" placeholder="Ҷустуҷӯи донишҷӯ...">
                </div>
            </div>
            <div class="col-md-2">
                <select id="rsGroup" class="form-select form-select-sm">
                    <option value="">Ҳама гурӯҳҳо</option>
                    @foreach(collect($results['rows'])->pluck('group')->unique()->sort() as $g)
                        <option value="{{ $g }}">{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select id="rsSubject" class="form-select form-select-sm">
                    <option value="">Ҳама фанҳо</option>
                    @foreach(collect($results['rows'])->pluck('subject')->unique()->sort() as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select id="rsStatus" class="form-select form-select-sm">
                    <option value="">Ҳама</option>
                    <option value="done">Супоридааст</option>
                    <option value="none">Ҳанӯз нест</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" id="rsReset" class="btn btn-outline-secondary btn-sm w-100">Тоза кардан</button>
            </div>
            <div class="col-md-1 text-end">
                <small class="text-muted" id="rsCounter"></small>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>Донишҷӯ</th>
                        <th>Гурӯҳ</th>
                        <th>Фан</th>
                        <th class="text-center">Кӯшиш</th>
                        <th class="text-center">Тест (%)</th>
                        <th class="text-center">Балл (аз {{ $results['test_max'] }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results['rows'] as $row)
                    <tr data-student="{{ mb_strtolower($row['student']) }}" data-group="{{ $row['group'] }}" data-subject="{{ $row['subject'] }}" data-status="{{ $row['pct'] !== null ? 'done' : 'none' }}">

                        <td>{{ $row['student'] }}</td>
                        <td><span class="badge bg-info">{{ $row['group'] }}</span></td>
                        <td>{{ $row['subject'] }}</td>
                        <td class="text-center">{{ $row['attempts'] ?? '—' }}</td>
                        <td class="text-center">
                            @if($row['pct'] !== null)
                            <span class="badge {{ $row['pct'] >= 50 ? 'bg-success' : 'bg-danger' }}">{{ $row['pct'] }}%</span>
                            @else
                            <span class="text-muted">ҳанӯз нест</span>
                            @endif
                        </td>
                        <td class="text-center fw-bold">{{ $row['scaled'] ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Натиҷа нест.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const search=document.getElementById('rsSearch');
  const group=document.getElementById('rsGroup');
  const subject=document.getElementById('rsSubject');
  const status=document.getElementById('rsStatus');
  const reset=document.getElementById('rsReset');
  const counter=document.getElementById('rsCounter');
  const rows=Array.from(document.querySelectorAll('tbody tr[data-student]'));
  const total=rows.length;
  function norm(s){return (s||'').toLowerCase().trim();}
  function apply(){
    const q=norm(search.value);
    const g=group.value;
    const s=subject.value;
    const st=status.value;
    let visible=0;
    rows.forEach(r=>{
      let ok=true;
      if(q && norm(r.dataset.student).indexOf(q)===-1) ok=false;
      if(ok && g && r.dataset.group!==g) ok=false;
      if(ok && s && r.dataset.subject!==s) ok=false;
      if(ok && st && r.dataset.status!==st) ok=false;
      r.style.display=ok?'':'none';
      if(ok) visible++;
    });
    counter.textContent='Нишон дода шуд: '+visible+' аз '+total;
    // empty message
    let emptyRow=document.getElementById('rsEmptyRow');
    if(!emptyRow){
      emptyRow=document.createElement('tr');
      emptyRow.id='rsEmptyRow';
      emptyRow.innerHTML='<td colspan="6" class="text-center text-muted py-4">Натиҷа ёфт нашуд.</td>';
      document.querySelector('tbody').appendChild(emptyRow);
    }
    emptyRow.style.display = visible===0 ? '' : 'none';
  }
  [search,group,subject,status].forEach(el=>{ if(el){ el.addEventListener('input',apply); el.addEventListener('change',apply);} });
  reset.addEventListener('click',function(){ search.value=''; group.value=''; subject.value=''; status.value=''; apply(); });
  apply();
});
</script>
@endsection