@extends('layouts.app')
@section('title', 'Modifica Militare - SUGECO')
@section('content')
    @include('militare.partials.form_militare', ['isEdit' => true])
@endsection

