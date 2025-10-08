<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-semibold text-white">Sign in to your merchant workspace</h2>
        <p class="mt-1 text-sm text-slate-400">Use the email and password you registered with to continue.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input
                id="email"
                class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-950/60 text-white focus:border-indigo-500 focus:ring-indigo-500"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="merchant@store.com"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-indigo-300 hover:text-indigo-200" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>
            <x-text-input
                id="password"
                class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-950/60 text-white focus:border-indigo-500 focus:ring-indigo-500"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-300">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-indigo-500 focus:ring-indigo-500"
                    name="remember"
                >
                <span>{{ __('Remember me on this device') }}</span>
            </label>
            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 border-t border-slate-800 pt-4 text-center text-sm text-slate-400">
        {{ __("Don't have an account yet?") }}
        <a href="{{ route('register') }}" class="font-medium text-indigo-300 hover:text-indigo-200">
            {{ __('Create a merchant account') }}
        </a>
    </div>
</x-guest-layout>
