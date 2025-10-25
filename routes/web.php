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


// Rotta della dashboard (principale)
Route::get('/', [DashboardController::class, 'index'])
    ->name('dashboard');

// Redirect da vecchie rotte militare a nuove rotte anagrafica
Route::redirect('/militare', '/anagrafica', 301);

// Route per la ricerca (Anagrafica)
Route::get('/anagrafica/search', [MilitareController::class, 'search'])
    ->name('anagrafica.search');

// API route per ottenere dati militare (per AJAX)
Route::get('/api/militari/{militare}', [MilitareController::class, 'getApiData'])
    ->name('api.militari.show');

/*
 |-------------------------------------------------
 | Rotte per la gestione dei militari
 |-------------------------------------------------
*/
// Rotte che devono essere definite PRIMA della resource route
Route::get('/anagrafica/export-excel', [MilitareController::class, 'exportExcel'])->name('anagrafica.export-excel');

// Resource route
Route::resource('anagrafica', MilitareController::class)->parameters(['anagrafica' => 'militare']);

// Altre rotte anagrafica (dopo resource per evitare conflitti)
Route::post('/anagrafica/{militare}/update-field', [MilitareController::class, 'updateField'])->name('anagrafica.update-field');
Route::post('/anagrafica/{militare}/patenti/add', [MilitareController::class, 'addPatente'])->name('anagrafica.patenti.add');
Route::post('/anagrafica/{militare}/patenti/remove', [MilitareController::class, 'removePatente'])->name('anagrafica.patenti.remove');

// Altre rotte militare
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
    ->name('organigramma');

Route::get('/organigramma/refresh', [OrganigrammaController::class, 'refreshCache'])
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

Route::prefix('board')->group(function () {
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
Route::prefix('cpt')->name('pianificazione.')->group(function () {
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
Route::prefix('servizi')->name('servizi.')->group(function () {
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
Route::prefix('trasparenza')->name('trasparenza.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TrasparenzaController::class, 'index'])->name('index');
    Route::get('/export-excel', [\App\Http\Controllers\TrasparenzaController::class, 'exportExcel'])->name('export-excel');
});

/*
|-------------------------------------------------
| Rotte per le Scadenze Certificati
|-------------------------------------------------
*/
Route::prefix('scadenze')->name('scadenze.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ScadenzeController::class, 'index'])->name('index');
    Route::post('/{militare}/update', [\App\Http\Controllers\ScadenzeController::class, 'update'])->name('update');
    Route::post('/{militare}/update-singola', [\App\Http\Controllers\ScadenzeController::class, 'updateSingola'])->name('update-singola');
});

/*
|-------------------------------------------------
|| Rotte per i Ruolini
|-------------------------------------------------
*/
Route::prefix('ruolini')->name('ruolini.')->group(function () {
    Route::get('/', [\App\Http\Controllers\RuoliniController::class, 'index'])->name('index');
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
