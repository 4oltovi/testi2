@extends('layouts.app')

@section('title', 'Таҳрири корманд')
@section('page-header', 'Таҳрири корманд')
@section('page-description', $employee->full_name)

@section('content')
    @include('hr.employees._form', [
        'employee' => $employee,
        'roles' => $roles,
        'selectedRoleIds' => $selectedRoleIds,
        'canChangeRoles' => $canChangeRoles,
    ])
@endsection