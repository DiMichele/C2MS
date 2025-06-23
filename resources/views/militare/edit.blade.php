@extends('layouts.app')
@section('title', 'Modifica Militare - C2MS')
@section('content')
    @include('militare.partials.form_militare', ['isEdit' => true])
@endsection

@section('scripts')
    @yield('page_scripts')
@endsection
