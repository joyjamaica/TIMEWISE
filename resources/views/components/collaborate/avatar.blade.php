@props(['user', 'size' => 'md'])
@php
    $initials = collect(preg_split('/\s+/', trim($user->name ?? '?')))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<span {{ $attributes->class(['clb-avatar', 'clb-avatar--'.$size]) }} title="{{ $user->name }}">{{ $initials }}</span>
