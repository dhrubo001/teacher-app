@extends('layouts.app')
@section('content')
    <livewire:student-list-under-class :class_id="$class_id" />
@endsection
