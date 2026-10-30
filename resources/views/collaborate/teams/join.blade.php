@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/collaborate.css') }}">
<div class="clb clb--narrow">
    <h1 class="clb-title">Join a team</h1>
    <p class="clb-sub">Enter the invitation code a teammate shared with you.</p>
    <div class="clb-panel">@include('collaborate.teams._join-form')</div>
</div>
@endsection
