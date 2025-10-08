<x-admin-auth-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Admin Email')" class="text-sm text-slate-300" />
            <x-text-input
                id="email"
                class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-950/60 text-white focus:border-rose-400 focus:ring-rose-400"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="ops@company.com"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" class="text-sm text-slate-300" />
                <label for="remember" class="inline-flex items-center gap-2 text-xs text-slate-400">
                    <input id="remember" type="checkbox" class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-rose-400 focus:ring-rose-400" name="remember">
                    <span>{{ __('Stay signed in') }}</span>
                </label>
            </div>
            <x-text-input
                id="password"
                class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-950/60 text-white focus:border-rose-400 focus:ring-rose-400"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end">
            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-admin-auth-layout>
