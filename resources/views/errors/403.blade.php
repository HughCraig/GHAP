@extends('templates.layout')

@section('content')
<div class="container">
    <h1>{{ $exception->getMessage() }}</h1>
    <br>
    <button onclick='window.history.back()'>Back</button>
</div>
@endsection
