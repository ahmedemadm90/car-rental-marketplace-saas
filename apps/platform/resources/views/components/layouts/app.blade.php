<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full" x-data="{ theme: localStorage.getItem('theme') ?? 'system' }" x-init="$watch('theme', value => { localStorage.setItem('theme', value); document.documentElement.classList.toggle('dark', value === 'dark' || (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)); })" :class="{ 'dark': theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches) }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'VoyagerRent') }}</title>
    <meta name="description" content="{{ $description ?? __('ui.meta_description') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-slate-50 font-sans text-slate-900 antialiased transition-colors dark:bg-slate-950 dark:text-slate-100">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-slate-900">{{ __('ui.skip_to_content') }}</a>
    <div class="min-h-screen">
        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8" aria-label="{{ __('ui.primary_navigation') }}">
                <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold tracking-tight text-slate-950 dark:text-white">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 text-lg font-black text-white shadow-lg shadow-blue-500/25">V</span>
                    <span>VoyagerRent</span>
                </a>
                <div class="hidden items-center gap-6 text-sm font-medium md:flex">
                    <a class="transition hover:text-cyan-600 dark:hover:text-cyan-400" href="{{ route('home') }}#search">{{ __('ui.search_cars') }}</a>
                    <a class="transition hover:text-cyan-600 dark:hover:text-cyan-400" href="{{ route('home') }}#how-it-works">{{ __('ui.how_it_works') }}</a>
                    @auth
                        <a class="transition hover:text-cyan-600 dark:hover:text-cyan-400" href="{{ route('dashboard') }}">{{ __('ui.dashboard') }}</a>
                    @endauth
                </div>
                <div class="flex items-center gap-2">
                    <form action="{{ route('locale') }}" method="POST">
                        @csrf
                        <input type="hidden" name="locale" value="{{ app()->getLocale() === 'en' ? 'ar' : 'en' }}">
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="{{ __('ui.switch_language') }}">{{ app()->getLocale() === 'en' ? 'العربية' : 'English' }}</button>
                    </form>
                    <button type="button" @click="theme = theme === 'dark' ? 'light' : 'dark'" class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="{{ __('ui.toggle_theme') }}">
                        <span class="dark:hidden">◐</span><span class="hidden dark:inline">◑</span>
                    </button>
                    @guest
                        <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:inline-flex dark:text-slate-200 dark:hover:bg-slate-800">{{ __('ui.sign_in') }}</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-600 dark:bg-cyan-500 dark:text-slate-950">{{ __('ui.create_account') }}</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-600 dark:bg-cyan-500 dark:text-slate-950">{{ __('ui.account') }}</a>
                    @endguest
                </div>
            </nav>
        </header>
        <main id="content">{{ $slot }}</main>
        <footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 dark:text-slate-400">
                <p>© {{ now()->year }} VoyagerRent. {{ __('ui.footer_rights') }}</p>
                <div class="flex gap-4"><a href="#" class="hover:text-cyan-600">{{ __('ui.privacy') }}</a><a href="#" class="hover:text-cyan-600">{{ __('ui.terms') }}</a><a href="#" class="hover:text-cyan-600">{{ __('ui.support') }}</a></div>
            </div>
        </footer>
    </div>
    @livewireScripts
</body>
</html>
