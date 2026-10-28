@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/collaborate.css') }}">
<div class="clb clb--narrow">
    <a class="clb-back" href="{{ route('collaborate.index') }}">Back to Collaborate</a>
    <h1 class="clb-title">Create a team</h1>
    <p class="clb-sub">You’ll be the owner and can invite others with a code.</p>
    <div class="clb-panel">@include('collaborate.teams._create-form')</div>
</div>
@endsection
