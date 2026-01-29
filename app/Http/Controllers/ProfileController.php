<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Mostra pagina profilo
     */
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    /**
     * Cambia password
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Inserisci la password attuale',
            'password.required' => 'Inserisci la nuova password',
            'password.min' => 'La password deve essere di almeno 8 caratteri',
            'password.confirmed' => 'Le password non corrispondono',
        ]);

        $user = Auth::user();

        // Verifica password attuale
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password attuale errata']);
        }

        // Aggiorna password
        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'last_password_change' => now(),
        ]);

        // Registra il cambio password nel log di audit
        AuditService::logPasswordChange($user);

        return back()->with('success', 'Password cambiata con successo!');
    }
}
