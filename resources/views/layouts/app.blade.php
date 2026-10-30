
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Home') | TIMEWISE</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Navigation Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/navigation.css') }}">

    {{-- Each page adds its own stylesheet here with @push('styles') --}}
    @stack('styles')
</head>

<body class="font-sans antialiased">

    <div class="tw-app">

        {{-- Breeze navigation customized as TimeWise sidebar --}}
        @include('layouts.navigation')

        <div class="tw-app-content">

            {{-- Mobile top bar --}}
            <header class="tw-topbar">
                <button type="button"
                    class="tw-topbar-btn"
                    id="twNavOpen"
                    aria-controls="twSidebar"
                    aria-expanded="false"
                    aria-label="Open navigation">

                    <svg width="22" height="22" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round"
                        aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <span class="tw-topbar-brand">Timewise</span>
            </header>

            {{-- Page Heading --}}
            @if (isset($header))
                <header>
                    {{ $header }}
                </header>
            @endif

            {{-- Page Content --}}
            <main>
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>

        </div>
    </div>

    <script src="{{ asset('js/navigation.js') }}" defer></script>

</body>
</html>
