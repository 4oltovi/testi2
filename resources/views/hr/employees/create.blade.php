@extends('layouts.app')

@section('title', 'Корманд нав')
@section('page-header', 'Корманд нав')
@section('page-description', 'Иловаи корбари корӣ')

@section('content')
    @include('hr.employees._form', [
        'employee' => $employee,
        'roles' => $roles,
        'selectedRoleIds' => $selectedRoleIds,
        'canChangeRoles' => true,
    ])
@endsection