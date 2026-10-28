<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Collaborate
        </h2>
    </x-slot>

<link rel="stylesheet" href="{{ asset('css/collaborate.css') }}">
<div class="clb">
    <header class="clb-head">
        <div>
            <h1 class="clb-title">Collaborate</h1>
            <p class="clb-sub">Your shared space for teams, tasks, and project progress.</p>
        </div>
        <div class="clb-head__actions">
            <button type="button" class="clb-btn clb-btn--ghost" onclick="document.getElementById('join-dialog').showModal()">Join Team</button>
            <button type="button" class="clb-btn clb-btn--dark" onclick="document.getElementById('create-dialog').showModal()">Create a Team</button>
        </div>
    </header>

    @include('collaborate._flash')

    @forelse ($teams as $team)
        @if ($loop->first)<section class="clb-teams">@endif
        <x-collaborate.team-card :team="$team" />
        @if ($loop->last)</section>@endif
    @empty
        <section class="clb-empty">
            <h2>You’re not on a team yet</h2>
            <p>Create a team to plan work together, or join one with an invitation code.</p>
            <div class="clb-head__actions">
                <button type="button" class="clb-btn clb-btn--ghost" onclick="document.getElementById('join-dialog').showModal()">Join Team</button>
                <button type="button" class="clb-btn clb-btn--dark" onclick="document.getElementById('create-dialog').showModal()">Create a Team</button>
            </div>
        </section>
    @endforelse
</div>

<dialog id="create-dialog" class="clb-dialog">
    <div class="clb-dialog__head"><h2>Create a team</h2>
        <button type="button" class="clb-x" aria-label="Close" onclick="this.closest('dialog').close()">×</button></div>
    @include('collaborate.teams._create-form')
</dialog>

<dialog id="join-dialog" class="clb-dialog">
    <div class="clb-dialog__head"><h2>Join a team</h2>
        <button type="button" class="clb-x" aria-label="Close" onclick="this.closest('dialog').close()">×</button></div>
    @include('collaborate.teams._join-form')
</dialog>

<script>
    // Reopen the right dialog when validation fails.
    @if ($errors->hasAny(['name', 'description', 'visibility']))
        document.getElementById('create-dialog').showModal();
    @elseif ($errors->has('invite_code'))
        document.getElementById('join-dialog').showModal();
    @endif
</script>
</x-app-layout>