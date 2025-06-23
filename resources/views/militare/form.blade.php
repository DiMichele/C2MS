{{--
|--------------------------------------------------------------------------
| Form unificato per Creazione/Modifica Militare
|--------------------------------------------------------------------------
| @version 1.0
| @author Michele Di Gennaro
--}}

@extends('layouts.app')
@section('title', (isset($militare) ? 'Modifica' : 'Nuovo') . ' Militare - C2MS')

@section('content')
    @include('militare.partials.form_militare', ['isEdit' => isset($militare)])
@endsection

@section('scripts')
    @yield('page_scripts')
@endsection 
