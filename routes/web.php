<?php

use App\Http\Controllers\MilitareController;
use App\Http\Controllers\OrganigrammaController;
use App\Http\Controllers\CertificatiController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AssenzeController;
use App\Http\Controllers\EventiController;

use App\Http\Controllers\NoteController;
use App\Http\Controllers\BoardController;


// Rotta della dashboard (principale)
Route::get('/', [DashboardController::class, 'index'])
    ->name('dashboard');

// Route per la ricerca (Militare)
Route::get('/militare/search', [MilitareController::class, 'search'])
    ->name('militare.search');

// API route per ottenere dati militare (per AJAX)
Route::get('/api/militari/{militare}', [MilitareController::class, 'getApiData'])
    ->name('api.militari.show');

/*
 |-------------------------------------------------
 | Rotte per la gestione dei militari
 |-------------------------------------------------
*/
Route::resource('militare', MilitareController::class);
Route::put('militare/{militare}/update-notes', [MilitareController::class, 'updateNotes'])->name('militare.update_notes');

// Rotte per gestione foto profilo
Route::get('/militare/{id}/foto', [MilitareController::class, 'getFoto'])->name('militare.foto');
Route::post('/militare/{id}/foto/upload', [MilitareController::class, 'uploadFoto'])->name('militare.foto.upload');
Route::delete('/militare/{id}/foto/delete', [MilitareController::class, 'deleteFoto'])->name('militare.foto.delete');

// Rotte per le valutazioni dei militari
Route::post('militare/{militare}/valutazioni', [MilitareController::class, 'storeValutazione'])->name('militare.valutazioni.store');
Route::post('militare/{militare}/valutazioni/field', [MilitareController::class, 'updateValutazioneField'])->name('militare.valutazioni.field');

// Rotta POST per aggiornamento AJAX del militare
Route::post('/militare/{id}', [MilitareController::class, 'update']);

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
 | Rotte per i certificati
 |-------------------------------------------------
*/
Route::prefix('certificati')->name('certificati.')->group(function () {
    Route::get('/corsi-lavoratori', [CertificatiController::class, 'corsiLavoratori'])->name('corsi_lavoratori');
    Route::get('/idoneita', [CertificatiController::class, 'idoneita'])->name('idoneita');
    Route::get('/create/{militare}/{tipo}', [CertificatiController::class, 'create'])->name('create');
    Route::get('/edit/{id}', [CertificatiController::class, 'edit'])->name('edit');
    Route::post('/upload', [CertificatiController::class, 'upload'])->name('upload');
    Route::post('/store', [CertificatiController::class, 'store'])->name('store');
    Route::put('/update/{id}', [CertificatiController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [CertificatiController::class, 'destroy'])->name('destroy');
});

// Rotte per le certificazioni - commentate per controller mancante
// Route::resource('certificati', CertificatoController::class);

// Rotte per le note
Route::post('/note/save', [NoteController::class, 'save'])->name('note.save');



/*
 |-------------------------------------------------
 | Rotte per assenze
 |-------------------------------------------------
*/
Route::get('/assenze', [AssenzeController::class, 'index'])->name('assenze.index');
Route::get('/assenze/create', [AssenzeController::class, 'create'])->name('assenze.create');
Route::post('/assenze', [AssenzeController::class, 'store'])->name('assenze.store');
Route::delete('/assenze/{assenza}', [AssenzeController::class, 'destroy'])->name('assenze.destroy');
Route::put('/assenze/{assenza}', [AssenzeController::class, 'update'])->name('assenze.update');


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
