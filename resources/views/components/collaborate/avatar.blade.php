@props(['user', 'size' => 'md'])
@php
    $initials = collect(preg_split('/\s+/', trim($user->name ?? '?')))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<span {{ $attributes->class(['clb-avatar', 'clb-avatar--'.$size]) }} title="{{ $user->name }}">
    @if ($user->profile_photo_path)
        <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="">
    @else
        {{ $initials }}
    @endif
</span>
