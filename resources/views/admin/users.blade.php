@extends('layouts.admin')

@section('title', __('Usuarios') . ' — ' . config('app.name'))

@section('page_header')
    <h1 class="text-2xl font-bold text-slate-900">{{ __('Usuarios') }}</h1>
@endsection

@section('page', 'admin-users-page')

@section('content')
    <div id="admin-users-page" class="card-tw overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 font-semibold">{{ __('Usuario') }}</th>
                    <th class="px-4 py-2 font-semibold">{{ __('Correo') }}</th>
                    <th class="px-4 py-2 font-semibold">{{ __('Tipo') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr data-user-id="{{ $user->id }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <select name="type" class="admin-user-type input-tw max-w-xs">
                                <option value="{{ $user->id }}.admin" @selected($user->type == 'admin')>Admin</option>
                                <option value="{{ $user->id }}.finance" @selected($user->type == 'finance')>Caja</option>
                                <option value="{{ $user->id }}.manager" @selected($user->type == 'manager')>Encargado</option>
                                <option value="{{ $user->id }}.waiter" @selected($user->type == 'waiter')>Camarera</option>
                                <option value="{{ $user->id }}.kitchen" @selected($user->type == 'kitchen')>Cocina</option>
                                <option value="{{ $user->id }}.employee" @selected($user->type == 'employee')>Empleado</option>
                                <option value="{{ $user->id }}.default" @selected($user->type == 'default')></option>
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
