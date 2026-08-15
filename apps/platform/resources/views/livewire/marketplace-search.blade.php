<section id="search" class="relative -mt-10 scroll-mt-24 px-4 pb-16 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl shadow-slate-900/10 dark:border-slate-800 dark:bg-slate-900 sm:p-7">
        <form wire:submit.prevent class="grid gap-4 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-3">
                <label for="pickup-branch" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('ui.pickup') }}</label>
                <select id="pickup-branch" wire:model.live="pickupBranchId" class="w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm outline-none ring-cyan-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-950">
                    @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }} — {{ $branch->city }}</option>@endforeach
                </select>
                @error('pickupBranchId')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="lg:col-span-3">
                <label for="pickup-at" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('ui.date_and_time') }}</label>
                <input id="pickup-at" type="datetime-local" wire:model.live.debounce.500ms="pickupAt" class="w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm outline-none ring-cyan-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-950">
                @error('pickupAt')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="lg:col-span-3">
                <label for="return-branch" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('ui.return') }}</label>
                <select id="return-branch" wire:model.live="returnBranchId" class="w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm outline-none ring-cyan-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-950">
                    @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }} — {{ $branch->city }}</option>@endforeach
                </select>
                @error('returnBranchId')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="lg:col-span-3">
                <label for="return-at" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('ui.date_and_time') }}</label>
                <input id="return-at" type="datetime-local" wire:model.live.debounce.500ms="returnAt" class="w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm outline-none ring-cyan-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-950">
                @error('returnAt')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </form>

        <div class="mt-6 flex flex-col gap-4 border-t border-slate-200 pt-5 dark:border-slate-800 lg:flex-row lg:items-center">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200"><span class="rounded-lg bg-cyan-100 px-2 py-1 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300">{{ __('ui.filters') }}</span></div>
            <select wire:model.live="category" class="rounded-lg border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="">{{ __('ui.vehicle_type') }}: {{ __('ui.all') }}</option><option value="SUV">SUV</option><option value="Sedan">Sedan</option><option value="Economy">Economy</option><option value="Luxury">Luxury</option></select>
            <select wire:model.live="transmission" class="rounded-lg border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="">{{ __('ui.transmission') }}: {{ __('ui.all') }}</option><option value="automatic">{{ __('ui.automatic') }}</option><option value="manual">{{ __('ui.manual') }}</option></select>
            <select wire:model.live="fuelType" class="rounded-lg border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="">{{ __('ui.fuel') }}: {{ __('ui.all') }}</option><option value="petrol">Petrol</option><option value="diesel">Diesel</option><option value="hybrid">Hybrid</option><option value="electric">Electric</option></select>
            <select wire:model.live="minimumSeats" class="rounded-lg border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="">{{ __('ui.seats') }}: {{ __('ui.all') }}</option><option value="2">2+</option><option value="4">4+</option><option value="5">5+</option><option value="7">7+</option></select>
            <div wire:loading class="text-sm font-medium text-cyan-600" role="status">{{ __('ui.search_available') }}…</div>
        </div>
    </div>

    <div class="mx-auto mt-10 max-w-7xl">
        <div class="mb-6 flex items-center justify-between"><h2 class="text-2xl font-bold tracking-tight">{{ __('ui.results') }}</h2><p class="text-sm text-slate-500 dark:text-slate-400">{{ $offers->count() }} {{ __('ui.compare') }}</p></div>
        @if($offers->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-100/60 p-12 text-center dark:border-slate-700 dark:bg-slate-900"><p class="mx-auto max-w-xl text-slate-600 dark:text-slate-300">{{ __('ui.no_results') }}</p></div>
        @else
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach($offers as $offer)
                    <article class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex h-40 items-center justify-center bg-gradient-to-br from-slate-100 via-cyan-50 to-blue-100 text-6xl dark:from-slate-800 dark:via-cyan-950 dark:to-blue-950">🚙</div>
                        <div class="p-5"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-widest text-cyan-600 dark:text-cyan-400">{{ $offer['group']->company->display_name }}</p><h3 class="mt-1 text-lg font-bold">{{ $offer['group']->name }}</h3><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $offer['group']->seats }} {{ __('ui.seats') }} · {{ ucfirst($offer['group']->transmission) }} · {{ ucfirst($offer['group']->fuel_type) }}</p></div><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">{{ $offer['available_units'] }} left</span></div>
                            <div class="mt-5 flex items-end justify-between border-t border-slate-100 pt-4 dark:border-slate-800"><div><p class="text-xs text-slate-500">{{ __('ui.from') }}</p><p class="text-xl font-black">{{ number_format($offer['quote']['total_minor'] / 100, 2) }} <span class="text-xs font-medium text-slate-500">{{ $offer['quote']['currency'] }}</span></p><p class="text-xs text-slate-500">{{ __('ui.total') }} · {{ $offer['quote']['days'] }} {{ __('ui.per_day') }}</p></div><a href="{{ route('register') }}" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-cyan-600 dark:bg-cyan-500 dark:text-slate-950">{{ __('ui.reserve') }}</a></div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
