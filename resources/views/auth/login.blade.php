@extends('layouts.user')
@section('content')
    <layout 
        :_user-count="{{ $userCount }}"
        :theme-settings="{{ json_encode($themeSettings) }}"
        _selected-language="spanish"
    ></layout>
@endsection