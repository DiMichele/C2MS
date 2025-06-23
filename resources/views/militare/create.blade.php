@extends('layouts.app')
@section('title', 'Nuovo Militare - C2MS')
@section('content')
    @include('militare.partials.form_militare', ['isEdit' => false])
@endsection

@section('scripts')
    @yield('page_scripts')
@endsection
