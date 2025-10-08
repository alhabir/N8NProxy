<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('merchants', 'email')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = null;

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $existingMerchant = Merchant::withTrashed()
                ->where('email', $validated['email'])
                ->first();

            if ($existingMerchant && $existingMerchant->user_id && $existingMerchant->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'email' => __('This email is already associated with another merchant account.'),
                ]);
            }

            if ($existingMerchant) {
                if ($existingMerchant->trashed()) {
                    $existingMerchant->restore();
                }

                $existingMerchant->fill([
                    'store_name' => $validated['name'],
                    'is_active' => true,
                    'is_approved' => false,
                ]);
                $existingMerchant->user_id = $user->id;
                $existingMerchant->email = $validated['email'];
                $existingMerchant->save();
            } else {
                Merchant::create([
                    'user_id' => $user->id,
                    'store_name' => $validated['name'],
                    'email' => $validated['email'],
                    'is_active' => true,
                    'is_approved' => false,
                ]);
            }

            DB::commit();
        } catch (ValidationException $exception) {
            DB::rollBack();
            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();
            report($exception);

            throw ValidationException::withMessages([
                'email' => __('Unable to register right now. Please try again or contact support.'),
            ]);
        }

        event(new Registered($user));

        Auth::guard('merchant')->login($user);

        return redirect()->intended(route('verification.notice'));
    }
}
