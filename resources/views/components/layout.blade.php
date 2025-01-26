<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Used Car Dealer</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

</head>
<body class="font-sans antialiased dark:bg-black dark:text-white/90 pb-20">
    <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
        <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">

            <header class="items-center gap-2 py-12 border-b-2 border-white/10">
                <div class="flex lg:justify-center">
                    <a href="/">
                        <h1 class="text-5xl dark:text-white">Used Car Dealer</h1>
                    </a>
                </div>
            </header>

        <div class="px-10 mt-6">
            <main class="mt-10 max-w-[986px] mx-auto">
                {{ $slot }}
            </main>
        </div>

    </div>
</div>
</body>
</html>
