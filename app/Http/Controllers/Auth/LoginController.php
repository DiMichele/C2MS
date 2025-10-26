<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Mostra il form di login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Gestisce il tentativo di login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ], [
            'username.required' => 'Lo username è obbligatorio',
            'password.required' => 'La password è obbligatoria',
        ]);

        // Rate limiting
        $this->ensureIsNotRateLimited($request);

        // Trova l'utente per username
        $user = User::where('username', $request->username)->first();

        // Se l'utente non esiste
        if (!$user) {
            RateLimiter::hit($this->throttleKey($request));
            throw ValidationException::withMessages([
                'username' => 'Username non trovato.',
            ]);
        }

        // Verifica password
        if (!Hash::check($request->password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request));
            throw ValidationException::withMessages([
                'password' => 'Password errata.',
            ]);
        }

        // Login
        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();
        RateLimiter::clear($this->throttleKey($request));

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Benvenuto, ' . $user->name . '!');
    }

    /**
     * Logout dell'utente
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Logout effettuato con successo!');
    }

    /**
     * Assicura che l'utente non sia rate limited
     */
    protected function ensureIsNotRateLimited(Request $request)
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'password' => 'Troppi tentativi di login. Riprova tra ' . ceil($seconds / 60) . ' minuti.',
        ]);
    }

    /**
     * Ottiene la chiave per il rate limiting
     */
    protected function throttleKey(Request $request)
    {
        return Str::transliterate($request->input('username') . '|' . $request->ip());
    }
}
