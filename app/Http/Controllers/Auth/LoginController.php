<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'L\'email è obbligatoria',
            'email.email' => 'Inserisci un\'email valida',
            'password.required' => 'La password è obbligatoria',
        ]);

        // Rate limiting per proteggere da brute force
        $this->ensureIsNotRateLimited($request);

        // Tentativo di autenticazione
        if (Auth::attempt(
            $request->only('email', 'password'),
            $request->filled('remember')
        )) {
            $request->session()->regenerate();
            RateLimiter::clear($this->throttleKey($request));

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Benvenuto, ' . Auth::user()->name . '!');
        }

        RateLimiter::hit($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => 'Le credenziali inserite non sono corrette.',
        ]);
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
            'email' => 'Troppi tentativi di login. Riprova tra ' . ceil($seconds / 60) . ' minuti.',
        ]);
    }

    /**
     * Ottiene la chiave per il rate limiting
     */
    protected function throttleKey(Request $request)
    {
        return Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());
    }
}
