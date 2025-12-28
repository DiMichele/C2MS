@extends('layouts.app')
@section('title', 'Nuovo Militare - SUGECO')
@section('content')
    @include('militare.partials.form_militare', ['isEdit' => false])
@endsection

