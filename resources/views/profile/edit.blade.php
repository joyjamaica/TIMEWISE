<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
    @endpush

    @php
        $profileInitials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    @endphp

    <div class="profile-page">
        <header class="profile-hero">
            <div class="profile-hero__copy">
                <span class="profile-eyebrow"><span></span> ACCOUNT SETTINGS</span>
                <h1>Make it yours<span>.</span></h1>
                <p>Manage your personal details, profile photo, and account security.</p>
            </div>

            <div class="profile-identity">
                <div class="profile-identity__avatar">
                    @if ($user->profile_photo_path)
                        <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="">
                    @else
                        {{ $profileInitials }}
                    @endif
                </div>
                <div class="profile-identity__text">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->email }}</span>
                </div>
                <span class="profile-identity__badge">Personal account</span>
            </div>

            <span class="profile-hero__orb profile-hero__orb--one" aria-hidden="true"></span>
            <span class="profile-hero__orb profile-hero__orb--two" aria-hidden="true"></span>
        </header>

        @if (session('status') === 'profile-updated' || session('status') === 'password-updated')
            <div class="profile-notice" role="status">
                <span class="profile-notice__check" aria-hidden="true">✓</span>
                <span>{{ session('status') === 'password-updated' ? 'Your password has been updated.' : 'Your profile has been saved.' }}</span>
            </div>
        @endif

        <div class="profile-grid">
            <div class="profile-main">
                <div class="profile-card profile-card--information">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="profile-card profile-card--security">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <aside class="profile-aside">
                <div class="profile-aside-card">
                    <div class="profile-aside-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 3 19 6v5c0 4.5-2.8 7.7-7 10-4.2-2.3-7-5.5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                    </div>
                    <span class="profile-aside-card__eyebrow">ACCOUNT SECURITY</span>
                    <h2>Keep your account safe</h2>
                    <p>Use a unique password you don't use on other websites. Updating it regularly helps protect your study space.</p>
                    <a href="#password-settings">Update password <span aria-hidden="true">→</span></a>
                </div>

                <div class="profile-card profile-card--danger">
                    @include('profile.partials.delete-user-form')
                </div>
            </aside>
        </div>

        <footer class="profile-footer">
            <span class="profile-footer__dot"></span>
            Your information is private and only visible to you.
        </footer>
    </div>
</x-app-layout>
