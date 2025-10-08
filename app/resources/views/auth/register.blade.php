<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-semibold text-white">Create your merchant account</h2>
        <p class="mt-1 text-sm text-slate-400">We will send a verification email to activate your dashboard.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="name" :value="__('Store or team name')" />
                <x-text-input
                    id="name"
                    class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-950/60 text-white focus:border-indigo-500 focus:ring-indigo-500"
                    type="text"
                    name="name"
                    :value="old('name')"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="My Salla Store"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="email" :value="__('Work email address')" />
                <x-text-input
                    id="email"
                    class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-950/60 text-white focus:border-indigo-500 focus:ring-indigo-500"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autocomplete="username"
                    placeholder="merchant@store.com"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="password" :value="__('Create password')" />
                <x-text-input
                    id="password"
                    class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-950/60 text-white focus:border-indigo-500 focus:ring-indigo-500"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                <x-text-input
                    id="password_confirmation"
                    class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-950/60 text-white focus:border-indigo-500 focus:ring-indigo-500"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-between">
            <p class="text-xs text-slate-400">
                {{ __('By continuing, you agree to receive onboarding tips regarding your merchant integration.') }}
            </p>
            <x-primary-button>
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 border-t border-slate-800 pt-4 text-center text-sm text-slate-400">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" class="font-medium text-indigo-300 hover:text-indigo-200">
            {{ __('Sign in instead') }}
        </a>
    </div>
</x-guest-layout>
