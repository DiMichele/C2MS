<?php

use App\Http\Controllers\MilitareController;
use App\Http\Controllers\OrganigrammaController;
use App\Http\Controllers\CertificatiController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EventiController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\PianificazioneController;
use App\Http\Controllers\Auth\LoginController;

/*
|-------------------------------------------------
| Rotte di Autenticazione
|-------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|-------------------------------------------------
| Rotte Pubbliche (solo Anagrafica)
|-------------------------------------------------
*/
// Route per la ricerca (Anagrafica)
Route::get('/anagrafica/search', [MilitareController::class, 'search'])
    ->name('anagrafica.search');

// API route per ottenere dati militare (per AJAX)
Route::get('/api/militari/{militare}', [MilitareController::class, 'getApiData'])
    ->name('api.militari.show');

// Rotte Anagrafica (pubbliche - accessibili senza login)
Route::get('/anagrafica', [MilitareController::class, 'index'])
    ->name('anagrafica.index');

// NOTA: La rotta parametrica /anagrafica/{militare} deve essere definita 
// DOPO tutte le altre rotte specifiche (come /anagrafica/create, /anagrafica/export-excel, ecc.)
// altrimenti cattura tutto come parametro militare

// Redirect da vecchie URL militare a anagrafica
Route::redirect('/militare', '/anagrafica', 301);

/*
|-------------------------------------------------
| Rotte Protette (richiedono autenticazione)
|-------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    
    // Rotta della dashboard (principale)
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    /*
     |-------------------------------------------------
     | Rotte Profilo Utente
     |-------------------------------------------------
    */
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/change-password', [\App\Http\Controllers\ProfileController::class, 'changePassword'])->name('profile.change-password');

    /*
     |-------------------------------------------------
     | Rotte Pannello Admin (solo per Admin e Amministratore)
     |-------------------------------------------------
    */
    Route::middleware(['permission:admin.access'])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard admin (redirect)
        Route::get('/', [\App\Http\Controllers\AdminController::class, 'index'])->name('index');
        
        // Gestione Utenti
        Route::get('/utenti', [\App\Http\Controllers\AdminController::class, 'usersIndex'])->name('users.index');
        Route::get('/utenti/nuovo', [\App\Http\Controllers\AdminController::class, 'create'])->name('create');
        Route::post('/utenti', [\App\Http\Controllers\AdminController::class, 'store'])->name('store');
        Route::get('/utenti/{user}/modifica', [\App\Http\Controllers\AdminController::class, 'edit'])->name('edit');
        Route::put('/utenti/{user}', [\App\Http\Controllers\AdminController::class, 'update'])->name('update');
        Route::delete('/utenti/{user}', [\App\Http\Controllers\AdminController::class, 'destroy'])->name('destroy');
        Route::post('/utenti/{user}/reset-password', [\App\Http\Controllers\AdminController::class, 'resetPassword'])->name('reset-password');
        
        // Gestione Permessi
        Route::get('/permessi', [\App\Http\Controllers\AdminController::class, 'permissionsIndex'])->name('permissions.index');
        Route::post('/permessi/ruoli/{role}', [\App\Http\Controllers\AdminController::class, 'updatePermissions'])->name('roles.permissions.update');
        
        // Gestione Ruoli
        Route::get('/ruoli/nuovo', [\App\Http\Controllers\AdminController::class, 'createRole'])->name('roles.create');
        Route::post('/ruoli', [\App\Http\Controllers\AdminController::class, 'storeRole'])->name('roles.store');
        Route::delete('/ruoli/{role}', [\App\Http\Controllers\AdminController::class, 'destroyRole'])->name('roles.destroy');
    });

    /*
     |-------------------------------------------------
     | Rotte per la gestione dei militari (protette)
     |-------------------------------------------------
    */
    // Export Excel Anagrafica (DEVE essere prima della rotta parametrica)
    Route::get('/anagrafica/export-excel', [MilitareController::class, 'exportExcel'])
        ->middleware('permission:anagrafica.view')
        ->name('anagrafica.export-excel');
    
    // Rotte create e store (richiedono permission:anagrafica.create)
    Route::get('/anagrafica/create', [MilitareController::class, 'create'])
        ->middleware('permission:anagrafica.create')
        ->name('anagrafica.create');
    Route::post('/anagrafica', [MilitareController::class, 'store'])
        ->middleware('permission:anagrafica.create')
        ->name('anagrafica.store');
    
    // Rotte edit e update (richiedono permission:anagrafica.edit)
    Route::get('/anagrafica/{militare}/edit', [MilitareController::class, 'edit'])
        ->middleware('permission:anagrafica.edit')
        ->name('anagrafica.edit');
    Route::put('/anagrafica/{militare}', [MilitareController::class, 'update'])
        ->middleware('permission:anagrafica.edit')
        ->name('anagrafica.update');
    Route::patch('/anagrafica/{militare}', [MilitareController::class, 'update']);
    
    // Rotta destroy (richiede permission:anagrafica.delete)
    Route::delete('/anagrafica/{militare}', [MilitareController::class, 'destroy'])
        ->middleware('permission:anagrafica.delete')
        ->name('anagrafica.destroy');

    // Altre rotte anagrafica (dopo resource per evitare conflitti)
    Route::post('/anagrafica/{militare}/update-field', [MilitareController::class, 'updateField'])
        ->middleware('permission:anagrafica.edit')
        ->name('anagrafica.update-field');
    
    Route::get('/anagrafica/plotoni-per-compagnia', [MilitareController::class, 'getPlotoniPerCompagnia'])
        ->middleware('permission:anagrafica.view')
        ->name('anagrafica.plotoni-per-compagnia');
    
    Route::post('/anagrafica/{militare}/patenti/add', [MilitareController::class, 'addPatente'])
        ->middleware('permission:anagrafica.edit')
        ->name('anagrafica.patenti.add');
    Route::post('/anagrafica/{militare}/patenti/remove', [MilitareController::class, 'removePatente'])
        ->middleware('permission:anagrafica.edit')
        ->name('anagrafica.patenti.remove');

// Altre rotte militare (CONTINUANO DENTRO IL MIDDLEWARE AUTH)
    Route::put('militare/{militare}/update-notes', [MilitareController::class, 'updateNotes'])->name('militare.update_notes');

    // Rotte per gestione foto profilo
    Route::get('/militare/{id}/foto', [MilitareController::class, 'getFoto'])->name('militare.foto');
    Route::post('/militare/{id}/foto/upload', [MilitareController::class, 'uploadFoto'])->name('militare.foto.upload');
    Route::delete('/militare/{id}/foto/delete', [MilitareController::class, 'deleteFoto'])->name('militare.foto.delete');

    // Rotte per le valutazioni dei militari
    Route::post('militare/{militare}/valutazioni', [MilitareController::class, 'storeValutazione'])->name('militare.valutazioni.store');
    Route::post('militare/{militare}/valutazioni/field', [MilitareController::class, 'updateValutazioneField'])->name('militare.valutazioni.field');

    // Rotta POST per aggiornamento AJAX
    Route::post('/anagrafica/{id}', [MilitareController::class, 'update']);

/*
 |-------------------------------------------------
 | Rotte per l'Organigramma
 |-------------------------------------------------
*/
Route::get('/organigramma', [OrganigrammaController::class, 'index'])
    ->middleware('permission:anagrafica.view')
    ->name('organigramma');

Route::get('/organigramma/refresh', [OrganigrammaController::class, 'refreshCache'])
    ->middleware('permission:anagrafica.view')
    ->name('organigramma.refresh');

/*
|-------------------------------------------------
| Rotte per i certificati (DEPRECATE - utilizzare Scadenze)
|-------------------------------------------------
*/
// Le pagine certificati/corsi-lavoratori e certificati/idoneita sono state rimosse
// Utilizzare la nuova pagina Scadenze in Personale > Scadenze

// Rotte per le certificazioni - commentate per controller mancante
// Route::resource('certificati', CertificatoController::class);

// Rotte per le note
Route::post('/note/save', [NoteController::class, 'save'])->name('note.save');



/*
 |-------------------------------------------------
 | Rotte per gli Eventi
 |-------------------------------------------------
*/
Route::get('/eventi', [EventiController::class, 'index'])->name('eventi.index');
Route::get('/eventi/create', [EventiController::class, 'create'])->name('eventi.create');
Route::post('/eventi', [EventiController::class, 'store'])->name('eventi.store');
Route::delete('/eventi/{evento}', [EventiController::class, 'destroy'])->name('eventi.destroy');

Route::prefix('board')->middleware('permission:board.view')->group(function () {
    Route::get('/', [BoardController::class, 'index'])->name('board.index');
    Route::get('/calendar', [BoardController::class, 'calendar'])->name('board.calendar');
    Route::get('/activities/{activity}', [BoardController::class, 'show'])->name('board.activities.show');
    Route::post('/activities', [BoardController::class, 'store'])->name('board.activities.store');
    Route::put('/activities/{activity}', [BoardController::class, 'update'])->name('board.activities.update');
    Route::delete('/activities/{activity}', [BoardController::class, 'destroy'])->name('board.activities.destroy');
    Route::patch('/activities/position', [BoardController::class, 'updatePosition'])->name('board.activities.position');
    Route::post('/activities/update-dates', [BoardController::class, 'updateDates'])->name('board.activities.update-dates');
    Route::patch('/activities/{activity}/autosave', [BoardController::class, 'autoSave'])->name('board.activities.autosave');
    
    // Rotte per la gestione dei militari associati
    Route::post('/activities/{activity}/militari', [BoardController::class, 'attachMilitare'])->name('board.activities.attach.militare');
    Route::delete('/activities/{activity}/militari/{militare}', [BoardController::class, 'detachMilitare'])->name('board.activities.detach.militare');
    
    // Rotte per la gestione degli allegati
    Route::post('/activities/{activity}/attachments', [BoardController::class, 'attachFile'])->name('board.activities.attach.file');
    Route::delete('/activities/{activity}/attachments/{attachment}', [BoardController::class, 'deleteAttachment'])->name('board.activities.detach.file');
});

/*
|-------------------------------------------------
| Rotte per il CPT (Controllo Presenza Truppe)
|-------------------------------------------------
*/
Route::prefix('cpt')->name('pianificazione.')->middleware('permission:cpt.view')->group(function () {
    Route::get('/', [PianificazioneController::class, 'index'])->name('index');
    Route::get('/test', function(Request $request) {
        // Stessa logica del controller principale ma vista semplificata
        $mese = $request->get('mese', Carbon\Carbon::now()->month);
        $anno = $request->get('anno', Carbon\Carbon::now()->year);
        
        $pianificazioneMensile = App\Models\PianificazioneMensile::where('mese', $mese)
            ->where('anno', $anno)
            ->first();
            
        if (!$pianificazioneMensile) {
            $pianificazioneMensile = App\Models\PianificazioneMensile::create([
                'mese' => $mese,
                'anno' => $anno,
                'nome' => Carbon\Carbon::createFromDate($anno, $mese, 1)->format('F Y'),
                'attiva' => true
            ]);
        }
        
        $militari = App\Models\Militare::with(['grado', 'plotone', 'pianificazioniGiornaliere'])
            ->orderByGradoENome()->get();
        
        $militariConPianificazione = [];
        foreach ($militari as $militare) {
            $pianificazioniPerGiorno = [];
            foreach ($militare->pianificazioniGiornaliere as $pianificazione) {
                $pianificazioniPerGiorno[$pianificazione->giorno] = $pianificazione;
            }
            $militariConPianificazione[] = [
                'militare' => $militare,
                'pianificazioni' => $pianificazioniPerGiorno
            ];
        }
        
        $giorniMese = range(1, 31);
        
        return view('pianificazione.test', compact('militariConPianificazione', 'giorniMese', 'mese', 'anno'));
    })->name('test');
    Route::get('/militare/{militare}', [PianificazioneController::class, 'militare'])->name('militare');
    Route::post('/militare/{militare}/update-giorno', [PianificazioneController::class, 'updateGiorno'])->name('militare.update-giorno');
    Route::post('/militare/{militare}/update-giorni-range', [PianificazioneController::class, 'updateGiorniRange'])->name('militare.update-giorni-range');
    Route::get('/export-excel', [PianificazioneController::class, 'exportExcel'])->name('export-excel');
});

/*
|-------------------------------------------------
| Rotte per la gestione dei Servizi e Turni
|-------------------------------------------------
*/
Route::prefix('servizi')->name('servizi.')->middleware('permission:servizi.view')->group(function () {
    Route::prefix('turni')->name('turni.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TurniController::class, 'index'])->name('index');
        Route::post('/check-disponibilita', [\App\Http\Controllers\TurniController::class, 'checkDisponibilita'])->name('check-disponibilita');
        Route::post('/assegna', [\App\Http\Controllers\TurniController::class, 'assegna'])->name('assegna');
        Route::post('/rimuovi', [\App\Http\Controllers\TurniController::class, 'rimuovi'])->name('rimuovi');
        Route::post('/copia-settimana', [\App\Http\Controllers\TurniController::class, 'copiaSettimana'])->name('copia-settimana');
        Route::post('/sincronizza', [\App\Http\Controllers\TurniController::class, 'sincronizza'])->name('sincronizza');
        Route::get('/export-excel', [\App\Http\Controllers\TurniController::class, 'exportExcel'])->name('export-excel');
    });
});

/*
|-------------------------------------------------
| Rotte per la Trasparenza Servizi
|-------------------------------------------------
*/
Route::prefix('trasparenza')->name('trasparenza.')->middleware('permission:servizi.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\TrasparenzaController::class, 'index'])->name('index');
    Route::get('/export-excel', [\App\Http\Controllers\TrasparenzaController::class, 'exportExcel'])->name('export-excel');
});

/*
|-------------------------------------------------
| Rotte per le Scadenze Certificati
|-------------------------------------------------
*/
Route::prefix('scadenze')->name('scadenze.')->middleware('permission:scadenze.view')->group(function () {
    // Pagina RSPP - Sicurezza sul Lavoro
    Route::get('/rspp', [\App\Http\Controllers\RsppController::class, 'index'])->name('rspp');
    Route::post('/rspp/{militare}/update-singola', [\App\Http\Controllers\RsppController::class, 'updateSingola'])
        ->middleware('permission:scadenze.edit')
        ->name('rspp.update-singola');
    Route::get('/rspp/export-excel', [\App\Http\Controllers\RsppController::class, 'exportExcel'])->name('rspp.export-excel');
    
    // Pagina Idoneità Sanitarie
    Route::get('/idoneita', [\App\Http\Controllers\IdoneitzController::class, 'index'])->name('idoneita');
    Route::post('/idoneita/{militare}/update-singola', [\App\Http\Controllers\IdoneitzController::class, 'updateSingola'])
        ->middleware('permission:scadenze.edit')
        ->name('idoneita.update-singola');
    Route::get('/idoneita/export-excel', [\App\Http\Controllers\IdoneitzController::class, 'exportExcel'])->name('idoneita.export-excel');
    
    // Pagina Poligoni - Tiri e Mantenimento
    Route::get('/poligoni', [\App\Http\Controllers\PoligoniController::class, 'index'])->name('poligoni');
    Route::post('/poligoni/{militare}/update-singola', [\App\Http\Controllers\PoligoniController::class, 'updateSingola'])
        ->middleware('permission:scadenze.edit')
        ->name('poligoni.update-singola');
    Route::get('/poligoni/export-excel', [\App\Http\Controllers\PoligoniController::class, 'exportExcel'])->name('poligoni.export-excel');
    
    // DEPRECATED - Vecchia pagina scadenze unificata (manteniamo per compatibilità)
    Route::get('/', [\App\Http\Controllers\ScadenzeController::class, 'index'])->name('index');
    Route::post('/{militare}/update', [\App\Http\Controllers\ScadenzeController::class, 'update'])->name('update');
    Route::post('/{militare}/update-singola', [\App\Http\Controllers\ScadenzeController::class, 'updateSingola'])->name('update-singola');
});

/*
|-------------------------------------------------
|| Rotte per i Ruolini
|-------------------------------------------------
*/
Route::prefix('ruolini')->name('ruolini.')->middleware('permission:anagrafica.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\RuoliniController::class, 'index'])->name('index');
});

/*
|-------------------------------------------------
|| Rotte per la Gestione CPT (Codici e Categorie)
|-------------------------------------------------
*/
Route::prefix('codici-cpt')->name('codici-cpt.')->middleware('permission:admin.access')->group(function () {
    Route::get('/', [\App\Http\Controllers\GestioneCptController::class, 'index'])->name('index');
    Route::get('/nuovo', [\App\Http\Controllers\GestioneCptController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\GestioneCptController::class, 'store'])->name('store');
    Route::get('/{codice}/modifica', [\App\Http\Controllers\GestioneCptController::class, 'edit'])->name('edit');
    Route::put('/{codice}', [\App\Http\Controllers\GestioneCptController::class, 'update'])->name('update');
    Route::delete('/{codice}', [\App\Http\Controllers\GestioneCptController::class, 'destroy'])->name('destroy');
    Route::patch('/{codice}/attiva', [\App\Http\Controllers\GestioneCptController::class, 'toggleAttivo'])->name('toggle');
    Route::post('/{codice}/duplica', [\App\Http\Controllers\GestioneCptController::class, 'duplicate'])->name('duplicate');
    Route::get('/esporta', [\App\Http\Controllers\GestioneCptController::class, 'export'])->name('export');
    Route::post('/aggiorna-ordine', [\App\Http\Controllers\GestioneCptController::class, 'updateOrder'])->name('update-order');
});

// Debug route per tunnel
Route::get('/debug-headers', function() {
    return response()->json([
        'host' => request()->header('Host'),
        'x-forwarded-host' => request()->header('X-Forwarded-Host'),
        'x-forwarded-proto' => request()->header('X-Forwarded-Proto'),
        'request_uri' => request()->server('REQUEST_URI'),
        'url_full' => request()->fullUrl(),
        'url_current' => url()->current(),
        'url_root' => url('/'),
        'config_app_url' => config('app.url'),
    ]);
});

// Debug route per asset CSS
Route::get('/debug-assets', function() {
    $cssFiles = [
        'global.css',
        'common.css',
        'components.css',
        'filters.css',
        'tooltips.css',
        'layout.css',
        'toast-system.css',
        'dashboard.css',
    ];
    
    $results = [];
    foreach ($cssFiles as $file) {
        $path = public_path("css/{$file}");
        $results[$file] = [
            'exists' => file_exists($path),
            'readable' => file_exists($path) && is_readable($path),
            'size' => file_exists($path) ? filesize($path) : 0,
            'url' => asset("css/{$file}"),
            'path' => $path,
        ];
    }
    
    return response()->json([
        'public_path' => public_path(),
        'asset_url' => asset('css/layout.css'),
        'app_url' => config('app.url'),
        'css_files' => $results,
    ], 200, [], JSON_PRETTY_PRINT);
});

}); // Fine middleware auth

// Rotta parametrica anagrafica (DEVE essere DOPO tutte le altre rotte specifiche)
// Altrimenti cattura /anagrafica/export-excel, /anagrafica/create, ecc. come ID militare
Route::get('/anagrafica/{militare}', [MilitareController::class, 'show'])
    ->name('anagrafica.show');

