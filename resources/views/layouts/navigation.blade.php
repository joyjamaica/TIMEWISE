
@php
    use Illuminate\Support\Facades\Route;

    $icons = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'tasks' => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="m8 12 3 3 5-6"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18"/>',
        'spark' => '<path d="M12 3c.6 4.8 3.2 7.4 8 8-4.8.6-7.4 3.2-8 8-.6-4.8-3.2-7.4-8-8 4.8-.6 7.4-3.2 8-8z"/>',
        'chart' => '<path d="M4 4v16h16M9 16v-5M13 16V8M17 16v-8"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5c2.1.7 3.5 2.6 3.5 5.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];

    // Update route names when your teammates' modules are available.
    $items = [
        [
            'label' => 'Dashboard',
            'icon' => 'grid',
            'routes' => ['dashboard'],
            'active' => ['dashboard'],
        ],
        [
            'label' => 'Tasks / Matrix',
            'icon' => 'tasks',
            'routes' => ['matrix.index'],
             'active' => ['matrix*'],
        ],
        [
            'label' => 'Calendar',
            'icon' => 'calendar',
            'routes' => ['calendar.index', 'calendar'],
            'active' => ['calendar*'],
        ],
        [
            'label' => 'PulseBot AI',
            'icon' => 'spark',
            'routes' => ['pulsebot.index', 'pulsebot', 'chatbot.index'],
            'active' => ['pulsebot*', 'chatbot*'],
        ],
        [
            'label' => 'Analytics',
            'icon' => 'chart',
            'routes' => ['analytics.index', 'analytics'],
            'active' => ['analytics*'],
        ],
        [
            'label' => 'Collaborate',
            'icon' => 'users',
            'routes' => ['collaborate.index', 'collaborate', 'teams.index'],
            'active' => ['collaborate*', 'teams*'],
        ],
    ];

    // Find the first route that actually exists.
    $resolve = function (array $routes) {
        foreach ($routes as $name) {
            if (Route::has($name)) {
                return $name;
            }
        }

        return null;
    };

    $user = Auth::user();
    $name = $user?->name ?? 'Guest';
    $email = $user?->email ?? 'Not logged in';

    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $initials = $initials ?: 'G';

    $dashboardRoute = $resolve(['dashboard']);
@endphp

<nav x-data="{ open: false }">

    {{-- Main Sidebar --}}
    <aside class="tw-sidebar" id="twSidebar" aria-label="Main navigation">

        {{-- Logo --}}
        <div class="tw-sidebar-head">
            <a href="{{ $dashboardRoute ? route($dashboardRoute) : url('/') }}" class="tw-brand">
                <span class="tw-brand-mark" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="4" y="11" width="4" height="9" rx="1"/>
                        <rect x="10" y="5" width="4" height="15" rx="1"/>
                        <rect x="16" y="9" width="4" height="11" rx="1"/>
                    </svg>
                </span>
                <span class="tw-brand-name">Timewise</span>
            </a>

            <button type="button" class="tw-sidebar-close" id="twNavClose" aria-label="Close navigation">
                &times;
            </button>
        </div>

        {{-- Navigation Links --}}
        <p class="tw-nav-label">Workspace</p>

        <div class="tw-nav">
            @foreach ($items as $item)
                @php
                    $routeName = $resolve($item['routes']);
                    $isActive = $routeName && request()->routeIs(...$item['active']);
                @endphp

                @if ($routeName)
                    <a
                        href="{{ route($routeName) }}"
                        class="tw-nav-link {{ $isActive ? 'is-active' : '' }}"
                        @if ($isActive) aria-current="page" @endif
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.7"
                            stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            {!! $icons[$item['icon']] !!}
                        </svg>

                        <span>{{ $item['label'] }}</span>

                        @if ($isActive)
                            <i class="tw-nav-dot" aria-hidden="true"></i>
                        @endif
                    </a>
                @else
                    <span class="tw-nav-link is-disabled"
                        aria-disabled="true"
                        title="This module isn't connected yet">
                        <svg width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.7"
                            stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            {!! $icons[$item['icon']] !!}
                        </svg>

                        <span>{{ $item['label'] }}</span>
                        <small class="tw-nav-soon">Soon</small>
                    </span>
                @endif
            @endforeach
        </div>

        {{-- User Profile --}}
        <div class="tw-profile">

         <a href="{{ route('profile.edit') }}" class="tw-profile-btn" title="Edit profile">
        <span class="tw-avatar" aria-hidden="true">
            @if ($user?->profile_photo_path)
                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="">
            @else
                {{ $initials }}
            @endif
        </span>

        <span class="tw-profile-text">
            <span class="tw-profile-name">{{ $name }}</span>
            <span class="tw-profile-sub">{{ $email }}</span>
            </span>
         </a>

    {{-- Logout Button --}}
    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit" class="tw-logout-btn">
            <svg width="20" height="20" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="1.7"
                stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <path d="M16 17l5-5-5-5"/>
                <path d="M21 12H9"/>
            </svg>

            <span>Log Out</span>
        </button>
    </form>

</div>


    </aside>

    {{-- Mobile Overlay --}}
    <div class="tw-overlay" id="twOverlay" hidden></div>

</nav>
