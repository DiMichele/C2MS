<?php

use App\Http\Controllers\MilitareController;
use App\Http\Controllers\OrganigrammaController;
use App\Http\Controllers\CertificatiController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])
        ->middleware('permission:profile.view')
        ->name('profile.index');
    Route::post('/profile/change-password', [\App\Http\Controllers\ProfileController::class, 'changePassword'])
        ->middleware('permission:profile.edit')
        ->name('profile.change-password');

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
        
        // Gestione Visibilità Compagnie per Ruolo
        Route::post('/ruoli/{role}/compagnie', [\App\Http\Controllers\AdminController::class, 'updateCompanyVisibility'])->name('roles.compagnie.update');

        // =====================================================================
        // Registro Attività (Audit Log)
        // =====================================================================
        Route::get('/registro-attivita', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/registro-attivita/esporta', [\App\Http\Controllers\AuditLogController::class, 'export'])->name('audit-logs.export');
        Route::get('/registro-attivita/accessi', [\App\Http\Controllers\AuditLogController::class, 'accessLogs'])->name('audit-logs.access');
        Route::get('/registro-attivita/utente/{user}', [\App\Http\Controllers\AuditLogController::class, 'userLogs'])->name('audit-logs.user');
        Route::get('/registro-attivita/{auditLog}', [\App\Http\Controllers\AuditLogController::class, 'show'])->name('audit-logs.show');
    });

    /*
     |-------------------------------------------------
     | Rotte per la gestione dei militari (protette)
     |-------------------------------------------------
    */
    // Rotta index anagrafica (richiede autenticazione e permesso view)
    Route::get('/anagrafica', [MilitareController::class, 'index'])
        ->middleware('permission:anagrafica.view')
        ->name('anagrafica.index');
    
    // Route per la ricerca (Anagrafica)
    Route::get('/anagrafica/search', [MilitareController::class, 'search'])
        ->middleware('permission:anagrafica.view')
        ->name('anagrafica.search');
    
    // API route per ottenere dati militare (per AJAX)
    Route::get('/api/militari/{militare}', [MilitareController::class, 'getApiData'])
        ->middleware('permission:anagrafica.view')
        ->name('api.militari.show');
    
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
    
    // Rotta per aggiornare campi custom
    Route::post('/anagrafica/{militare}/update-campo-custom', [MilitareController::class, 'updateCampoCustom'])
        ->middleware('permission:anagrafica.edit')
        ->name('anagrafica.update-campo-custom');
    
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
    ->middleware('permission:organigramma.view')
    ->name('organigramma');

Route::get('/organigramma/refresh', [OrganigrammaController::class, 'refreshCache'])
    ->middleware('permission:organigramma.view')
    ->name('organigramma.refresh');

Route::get('/organigramma/export-excel', [OrganigrammaController::class, 'exportExcel'])
    ->middleware('permission:organigramma.view')
    ->name('organigramma.export-excel');

/*
|-------------------------------------------------
| Rotte per i certificati (DEPRECATE - utilizzare Scadenze)
|-------------------------------------------------
*/
// Le pagine certificati/corsi-lavoratori e certificati/idoneita sono state rimosse
// Utilizzare la nuova pagina Scadenze in Personale > Scadenze

// Rotte per le certificazioni - commentate per controller mancante
// Route::resource('certificati', CertificatoController::class);

// Rotte per le note (rimossa - NoteController non esiste)



/*
 |-------------------------------------------------
 | NOTA: Rotte eventi RIMOSSE - Usare Board Attività
 |-------------------------------------------------
*/

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
    
    // Rotte per l'export Excel
    Route::get('/activities/{activity}/export', [BoardController::class, 'exportActivity'])->name('board.activities.export');
    Route::get('/export', [BoardController::class, 'exportBoard'])->name('board.export');
});

/*
|-------------------------------------------------
| Rotte per il CPT (Controllo Presenza Truppe)
|-------------------------------------------------
*/
Route::prefix('cpt')->name('pianificazione.')->middleware('permission:pianificazione.view')->group(function () {
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
    Route::post('/militare/{militare}/update-giorno', [PianificazioneController::class, 'updateGiorno'])
        ->middleware('permission:pianificazione.edit')
        ->name('militare.update-giorno');
    Route::post('/militare/{militare}/update-giorni-range', [PianificazioneController::class, 'updateGiorniRange'])
        ->middleware('permission:pianificazione.edit')
        ->name('militare.update-giorni-range');
    Route::get('/export-excel', [PianificazioneController::class, 'exportExcel'])->name('export-excel');
});

/*
|-------------------------------------------------
| Rotte per la gestione dei Servizi e Turni
|-------------------------------------------------
*/
Route::prefix('servizi')->name('servizi.')->middleware('permission:servizi.view')->group(function () {
    Route::prefix('turni')->name('turni.')->middleware('permission:turni.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\TurniController::class, 'index'])->name('index');
        Route::post('/check-disponibilita', [\App\Http\Controllers\TurniController::class, 'checkDisponibilita'])->name('check-disponibilita');
        Route::post('/militari-disponibilita', [\App\Http\Controllers\TurniController::class, 'getMilitariConDisponibilita'])->name('militari-disponibilita');
        Route::post('/assegna', [\App\Http\Controllers\TurniController::class, 'assegna'])
            ->middleware('permission:turni.edit')
            ->name('assegna');
        Route::post('/rimuovi', [\App\Http\Controllers\TurniController::class, 'rimuovi'])
            ->middleware('permission:turni.edit')
            ->name('rimuovi');
        Route::get('/assegnazioni', [\App\Http\Controllers\TurniController::class, 'getAssegnazioni'])
            ->name('assegnazioni');
        Route::post('/copia-settimana', [\App\Http\Controllers\TurniController::class, 'copiaSettimana'])
            ->middleware('permission:turni.edit')
            ->name('copia-settimana');
        Route::post('/sincronizza', [\App\Http\Controllers\TurniController::class, 'sincronizza'])
            ->middleware('permission:turni.edit')
            ->name('sincronizza');
        Route::get('/export-excel', [\App\Http\Controllers\TurniController::class, 'exportExcel'])->name('export-excel');
        Route::post('/comandante', [\App\Http\Controllers\TurniController::class, 'aggiornaComandante'])
            ->middleware('permission:turni.edit')
            ->name('comandante.update');
        Route::post('/servizi/impostazioni', [\App\Http\Controllers\TurniController::class, 'aggiornaImpostazioniServizio'])
            ->middleware('permission:turni.edit')
            ->name('servizi.update-settings');
        Route::post('/servizi/impostazioni-batch', [\App\Http\Controllers\TurniController::class, 'aggiornaImpostazioniBatch'])
            ->middleware('permission:turni.edit')
            ->name('servizi.update-settings-batch');
    });
});

/*
|-------------------------------------------------
| Rotte per la Trasparenza Servizi
|-------------------------------------------------
*/
Route::prefix('trasparenza')->name('trasparenza.')->middleware('permission:trasparenza.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\TrasparenzaController::class, 'index'])->name('index');
    Route::get('/export-excel', [\App\Http\Controllers\TrasparenzaController::class, 'exportExcel'])->name('export-excel');
});

/*
|-------------------------------------------------
| Rotte per la Disponibilità Personale
|-------------------------------------------------
*/
Route::prefix('disponibilita')->name('disponibilita.')->middleware('auth')->group(function () {
    // Panoramica disponibilità per polo
    Route::get('/', [\App\Http\Controllers\DisponibilitaController::class, 'index'])
        ->middleware('permission:cpt.view')
        ->name('index');
    
    // Vista impegni singolo militare
    Route::get('/militare/{militare}', [\App\Http\Controllers\DisponibilitaController::class, 'militare'])
        ->middleware('permission:cpt.view')
        ->name('militare');
    
    // API: Ottieni militari liberi per una data
    Route::get('/militari-liberi', [\App\Http\Controllers\DisponibilitaController::class, 'getMilitariLiberi'])
        ->middleware('permission:cpt.view')
        ->name('militari-liberi');
});

/*
|-------------------------------------------------
| Rotte per le Scadenze Certificati
|-------------------------------------------------
*/
// Redirect vecchia pagina scadenze alla dashboard (deprecata)
Route::get('/scadenze', function() {
    return redirect()->route('dashboard')->with('info', 'La pagina Scadenze è stata sostituita. Usa le sezioni SPP, Idoneità e Poligoni nel menu.');
})->middleware('auth')->name('scadenze.index');

// Gruppo SPP - Servizio di Prevenzione e Protezione
Route::prefix('spp')->name('spp.')->middleware('permission:scadenze.view')->group(function () {
    // Corsi di Formazione SPP (ex RSPP)
    Route::get('/corsi-di-formazione', [\App\Http\Controllers\RsppController::class, 'index'])->name('corsi-di-formazione');
    Route::post('/corsi-di-formazione/{militare}/update-singola', [\App\Http\Controllers\RsppController::class, 'updateSingola'])
        ->middleware('permission:scadenze.edit')
        ->name('corsi-di-formazione.update-singola');
    Route::get('/corsi-di-formazione/export-excel', [\App\Http\Controllers\RsppController::class, 'exportExcel'])->name('corsi-di-formazione.export-excel');

    // Corsi Accordo Stato Regione
    Route::get('/corsi-accordo-stato-regione', [\App\Http\Controllers\CorsiAccordoStatoRegioneController::class, 'index'])->name('corsi-accordo-stato-regione');
    Route::post('/corsi-accordo-stato-regione/{militare}/update-singola', [\App\Http\Controllers\CorsiAccordoStatoRegioneController::class, 'updateSingola'])
        ->middleware('permission:scadenze.edit')
        ->name('corsi-accordo-stato-regione.update-singola');
    Route::get('/corsi-accordo-stato-regione/export-excel', [\App\Http\Controllers\CorsiAccordoStatoRegioneController::class, 'exportExcel'])->name('corsi-accordo-stato-regione.export-excel');
});

// Idoneità Sanitarie
Route::prefix('idoneita')->name('idoneita.')->middleware('permission:scadenze.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\IdoneitzController::class, 'index'])->name('index');
    Route::post('/{militare}/update-singola', [\App\Http\Controllers\IdoneitzController::class, 'updateSingola'])
        ->middleware('permission:scadenze.edit')
        ->name('update-singola');
    Route::get('/export-excel', [\App\Http\Controllers\IdoneitzController::class, 'exportExcel'])->name('export-excel');
});

// Poligoni - Tiri e Mantenimento
Route::prefix('poligoni')->name('poligoni.')->middleware('permission:scadenze.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\PoligoniController::class, 'index'])->name('index');
    Route::post('/{militare}/update-singola', [\App\Http\Controllers\PoligoniController::class, 'updateSingola'])
        ->middleware('permission:scadenze.edit')
        ->name('update-singola');
    Route::get('/export-excel', [\App\Http\Controllers\PoligoniController::class, 'exportExcel'])->name('export-excel');
});

/*
|-------------------------------------------------
|| Rotte per gli Impieghi Personale (Teatri Operativi)
|-------------------------------------------------
*/
Route::prefix('impieghi-personale')->name('impieghi-personale.')->middleware('permission:scadenze.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'index'])->name('index');
    Route::get('/export-excel', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'exportExcel'])->name('export-excel');
    
    // Gestione Teatri Operativi
    Route::post('/teatri', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'storeTeatro'])
        ->middleware('permission:scadenze.edit')
        ->name('teatri.store');
    Route::put('/teatri/{id}', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'updateTeatro'])
        ->middleware('permission:scadenze.edit')
        ->name('teatri.update');
    Route::delete('/teatri/{id}', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'destroyTeatro'])
        ->middleware('permission:admin.access')
        ->name('teatri.destroy');
    
    // Gestione Militari nei Teatri
    Route::post('/militari/assegna', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'assegnaMilitare'])
        ->middleware('permission:scadenze.edit')
        ->name('militari.assegna');
    Route::delete('/militari/rimuovi', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'rimuoviMilitare'])
        ->middleware('permission:scadenze.edit')
        ->name('militari.rimuovi');
    Route::post('/militari/stato', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'updateStatoAssegnazione'])
        ->middleware('permission:scadenze.edit')
        ->name('militari.stato');
    Route::post('/militari/update', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'updateAssegnazione'])
        ->middleware('permission:scadenze.edit')
        ->name('militari.update');
    Route::post('/militari/conferma-tutti', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'confermaTutti'])
        ->middleware('permission:scadenze.edit')
        ->name('militari.conferma-tutti');
    
    // API per integrazione esterna
    Route::get('/api/teatri-confermati', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'getTeatriConfermati'])->name('api.teatri-confermati');
    Route::get('/api/teatri/{teatro}/militari', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'getMilitariConfermati'])->name('api.militari-confermati');
    
    // API per la pagina (AJAX)
    Route::get('/api/teatri', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'apiGetTeatri'])->name('api.teatri');
    Route::get('/api/militari', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'apiGetMilitari'])->name('api.militari');
    Route::get('/api/teatro/{id}', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'apiGetTeatro'])->name('api.teatro');
    Route::get('/api/teatro/{id}/disponibili', [\App\Http\Controllers\ImpieghiPersonaleController::class, 'apiGetMilitariDisponibili'])->name('api.militari-disponibili');
});

/*
|-------------------------------------------------
|| Rotte per gli Approntamenti
|-------------------------------------------------
*/
Route::prefix('approntamenti')->name('approntamenti.')->middleware('permission:scadenze.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\ApprontamentiController::class, 'index'])->name('index');
    Route::post('/{militare}/update-singola', [\App\Http\Controllers\ApprontamentiController::class, 'updateSingola'])
        ->middleware('permission:scadenze.edit')
        ->name('update-singola');
    Route::get('/export-excel', [\App\Http\Controllers\ApprontamentiController::class, 'exportExcel'])->name('export-excel');
    
    // Prenotazioni cattedre
    Route::post('/proponi-prenotazione', [\App\Http\Controllers\ApprontamentiController::class, 'proponiPrenotazione'])
        ->middleware('permission:scadenze.edit')
        ->name('proponi-prenotazione');
    Route::post('/salva-prenotazione', [\App\Http\Controllers\ApprontamentiController::class, 'salvaPrenotazione'])
        ->middleware('permission:scadenze.edit')
        ->name('salva-prenotazione');
    Route::post('/conferma-prenotazione', [\App\Http\Controllers\ApprontamentiController::class, 'confermaPrenotazione'])
        ->middleware('permission:scadenze.edit')
        ->name('conferma-prenotazione');
    Route::get('/statistiche-cattedre', [\App\Http\Controllers\ApprontamentiController::class, 'getStatisticheCattedre'])
        ->name('statistiche-cattedre');
    Route::post('/save-config-colonne', [\App\Http\Controllers\ApprontamentiController::class, 'saveConfigColonne'])
        ->middleware('permission:admin.access')
        ->name('save-config-colonne');
    Route::get('/export-proposta', [\App\Http\Controllers\ApprontamentiController::class, 'exportProposta'])
        ->name('export-proposta');
    
    // =====================================================================
    // MCM - Gestione ore e prenotazioni multi-giorno
    // MCM richiede 40 ore totali (Lun-Gio: 8h, Ven: 4h)
    // Requisiti: Idoneità SMI o T.O. valida
    // =====================================================================
    Route::post('/mcm/proponi-prenotazione', [\App\Http\Controllers\ApprontamentiController::class, 'proponiPrenotazioneMcm'])
        ->middleware('permission:scadenze.edit')
        ->name('mcm.proponi-prenotazione');
    Route::post('/mcm/salva-prenotazione', [\App\Http\Controllers\ApprontamentiController::class, 'salvaPrenotazioneMcm'])
        ->middleware('permission:scadenze.edit')
        ->name('mcm.salva-prenotazione');
    Route::get('/mcm/militare/{militare}', [\App\Http\Controllers\ApprontamentiController::class, 'getDettagliMcmMilitare'])
        ->name('mcm.dettagli-militare');
    Route::post('/mcm/conferma-sessione', [\App\Http\Controllers\ApprontamentiController::class, 'confermaSessioneMcm'])
        ->middleware('permission:scadenze.edit')
        ->name('mcm.conferma-sessione');
    Route::post('/mcm/annulla-sessione', [\App\Http\Controllers\ApprontamentiController::class, 'annullaSessioneMcm'])
        ->middleware('permission:scadenze.edit')
        ->name('mcm.annulla-sessione');
    Route::post('/mcm/calcola-ore', [\App\Http\Controllers\ApprontamentiController::class, 'calcolaOreMcm'])
        ->name('mcm.calcola-ore');
    
    // Export Excel militari proposti per prenotazione
    Route::get('/export-proposta-militari', [\App\Http\Controllers\ApprontamentiController::class, 'exportPropostaMilitari'])
        ->name('export-proposta-militari');
    
    // =====================================================================
    // Gestione Prenotazioni Attive
    // =====================================================================
    Route::get('/prenotazioni-attive', [\App\Http\Controllers\ApprontamentiController::class, 'getPrenotazioniAttive'])
        ->name('prenotazioni-attive');
    Route::post('/verifica-disponibilita', [\App\Http\Controllers\ApprontamentiController::class, 'verificaDisponibilita'])
        ->name('verifica-disponibilita');
    Route::post('/modifica-prenotazione-multipla', [\App\Http\Controllers\ApprontamentiController::class, 'modificaPrenotazioneMultipla'])
        ->middleware('permission:scadenze.edit')
        ->name('modifica-prenotazione-multipla');
    Route::post('/conferma-prenotazione-multipla', [\App\Http\Controllers\ApprontamentiController::class, 'confermaPrenotazioneMultipla'])
        ->middleware('permission:scadenze.edit')
        ->name('conferma-prenotazione-multipla');
    Route::post('/annulla-prenotazione', [\App\Http\Controllers\ApprontamentiController::class, 'annullaPrenotazione'])
        ->middleware('permission:scadenze.edit')
        ->name('annulla-prenotazione');
    Route::get('/export-prenotazioni-excel', [\App\Http\Controllers\ApprontamentiController::class, 'exportPrenotazioniExcel'])
        ->name('export-prenotazioni-excel');
});

// Gestione Approntamenti (Admin) - Configurazione colonne
Route::prefix('gestione-approntamenti')->name('gestione-approntamenti.')->middleware('permission:admin.access')->group(function () {
    Route::get('/', [\App\Http\Controllers\ApprontamentiController::class, 'gestione'])->name('index');
    Route::post('/colonne', [\App\Http\Controllers\ApprontamentiController::class, 'storeColonna'])->name('colonne.store');
    Route::put('/colonne/{id}', [\App\Http\Controllers\ApprontamentiController::class, 'updateColonna'])->name('colonne.update');
    Route::delete('/colonne/{id}', [\App\Http\Controllers\ApprontamentiController::class, 'destroyColonna'])->name('colonne.destroy');
    Route::post('/colonne/ordine', [\App\Http\Controllers\ApprontamentiController::class, 'updateOrdine'])->name('colonne.ordine');
    Route::post('/colonne/{id}/toggle', [\App\Http\Controllers\ApprontamentiController::class, 'toggleColonna'])->name('colonne.toggle');
});

/*
|-------------------------------------------------
|| Rotte per i Ruolini
|-------------------------------------------------
*/
Route::prefix('ruolini')->name('ruolini.')->middleware('permission:ruolini.view')->group(function () {
    Route::get('/', [\App\Http\Controllers\RuoliniController::class, 'index'])->name('index');
    Route::get('/export-excel', [\App\Http\Controllers\RuoliniController::class, 'exportExcel'])->name('export-excel');
    Route::get('/export-rapportino', [\App\Http\Controllers\RuoliniController::class, 'exportRapportino'])->name('export-rapportino');
});

/*
|-------------------------------------------------
| Rotte per la Gestione Ruolini (Configurazione)
|-------------------------------------------------
*/
Route::prefix('gestione-ruolini')->name('gestione-ruolini.')->middleware('auth')->group(function () {
    Route::get('/', [\App\Http\Controllers\GestioneRuoliniController::class, 'index'])->name('index');
    Route::post('/{tipoServizioId}', [\App\Http\Controllers\GestioneRuoliniController::class, 'update'])->name('update');
    Route::post('/batch/update', [\App\Http\Controllers\GestioneRuoliniController::class, 'updateBatch'])->name('update-batch');
    Route::post('/default-stato', [\App\Http\Controllers\GestioneRuoliniController::class, 'updateDefaultStato'])->name('update-default-stato');
    Route::get('/rules', [\App\Http\Controllers\GestioneRuoliniController::class, 'getRules'])->name('rules');
    Route::delete('/{tipoServizioId}', [\App\Http\Controllers\GestioneRuoliniController::class, 'destroy'])->name('destroy');
});

/*
|-------------------------------------------------
| Rotte per la Gestione SPP (Configurazione Corsi)
|-------------------------------------------------
*/
Route::prefix('gestione-spp')->name('gestione-spp.')->middleware('permission:admin.access')->group(function () {
    Route::get('/', [\App\Http\Controllers\GestioneSppController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\GestioneSppController::class, 'store'])->name('store');
    Route::post('/{id}', [\App\Http\Controllers\GestioneSppController::class, 'update'])->name('update');
    Route::put('/{id}/edit', [\App\Http\Controllers\GestioneSppController::class, 'edit'])->name('edit');
    Route::delete('/{id}', [\App\Http\Controllers\GestioneSppController::class, 'destroy'])->name('destroy');
});

/*
|-------------------------------------------------
| Rotte per la Gestione Poligoni (Configurazione Tipi)
|-------------------------------------------------
*/
Route::prefix('gestione-poligoni')->name('gestione-poligoni.')->middleware('permission:admin.access')->group(function () {
    Route::get('/', [\App\Http\Controllers\GestionePoligoniController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\GestionePoligoniController::class, 'store'])->name('store');
    Route::post('/{id}', [\App\Http\Controllers\GestionePoligoniController::class, 'update'])->name('update');
    Route::put('/{id}/edit', [\App\Http\Controllers\GestionePoligoniController::class, 'edit'])->name('edit');
    Route::delete('/{id}', [\App\Http\Controllers\GestionePoligoniController::class, 'destroy'])->name('destroy');
});

/*
|-------------------------------------------------
| Rotte per la Gestione Configurazione Idoneità
|-------------------------------------------------
*/
Route::prefix('gestione-idoneita')->name('gestione-idoneita.')->middleware('permission:admin.access')->group(function () {
    Route::get('/', [\App\Http\Controllers\GestioneIdoneitaController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\GestioneIdoneitaController::class, 'store'])->name('store');
    Route::post('/{id}', [\App\Http\Controllers\GestioneIdoneitaController::class, 'update'])->name('update');
    Route::put('/{id}/edit', [\App\Http\Controllers\GestioneIdoneitaController::class, 'edit'])->name('edit');
    Route::delete('/{id}', [\App\Http\Controllers\GestioneIdoneitaController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle', [\App\Http\Controllers\GestioneIdoneitaController::class, 'toggleActive'])->name('toggle');
});

/*
|-------------------------------------------------
| Rotte per la Gestione Configurazione Anagrafica
|-------------------------------------------------
*/
Route::prefix('gestione-anagrafica-config')->name('gestione-anagrafica-config.')->middleware('permission:admin.access')->group(function () {
    Route::get('/', [\App\Http\Controllers\GestioneAnagraficaConfigController::class, 'index'])->name('index');
    
    // Plotoni
    Route::post('/plotoni', [\App\Http\Controllers\GestioneAnagraficaConfigController::class, 'storePlotone'])->name('plotoni.store');
    Route::put('/plotoni/{id}', [\App\Http\Controllers\GestioneAnagraficaConfigController::class, 'updatePlotone'])->name('plotoni.update');
    Route::delete('/plotoni/{id}', [\App\Http\Controllers\GestioneAnagraficaConfigController::class, 'destroyPlotone'])->name('plotoni.destroy');
    
    // Uffici
    Route::post('/uffici', [\App\Http\Controllers\GestioneAnagraficaConfigController::class, 'storeUfficio'])->name('uffici.store');
    Route::put('/uffici/{id}', [\App\Http\Controllers\GestioneAnagraficaConfigController::class, 'updateUfficio'])->name('uffici.update');
    Route::delete('/uffici/{id}', [\App\Http\Controllers\GestioneAnagraficaConfigController::class, 'destroyUfficio'])->name('uffici.destroy');
    
    // Incarichi
    Route::post('/incarichi', [\App\Http\Controllers\GestioneAnagraficaConfigController::class, 'storeIncarico'])->name('incarichi.store');
    Route::put('/incarichi/{id}', [\App\Http\Controllers\GestioneAnagraficaConfigController::class, 'updateIncarico'])->name('incarichi.update');
    Route::delete('/incarichi/{id}', [\App\Http\Controllers\GestioneAnagraficaConfigController::class, 'destroyIncarico'])->name('incarichi.destroy');
});

/*
|-------------------------------------------------
| Rotte per la Gestione Campi Anagrafica (Colonne Custom)
|-------------------------------------------------
*/
Route::prefix('gestione-campi-anagrafica')->name('gestione-campi-anagrafica.')->middleware('permission:admin.access')->group(function () {
    Route::get('/', [\App\Http\Controllers\GestioneCampiAnagraficaController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\GestioneCampiAnagraficaController::class, 'store'])->name('store');
    Route::put('/{id}/edit', [\App\Http\Controllers\GestioneCampiAnagraficaController::class, 'edit'])->name('edit');
    Route::post('/{id}/order', [\App\Http\Controllers\GestioneCampiAnagraficaController::class, 'updateOrder'])->name('update-order');
    Route::post('/{id}/toggle', [\App\Http\Controllers\GestioneCampiAnagraficaController::class, 'toggleActive'])->name('toggle');
    Route::delete('/{id}', [\App\Http\Controllers\GestioneCampiAnagraficaController::class, 'destroy'])->name('destroy');
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

// Debug routes - SOLO in ambiente di sviluppo e per admin
if (config('app.debug')) {
    Route::middleware(['auth', 'permission:admin.access'])->group(function () {
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
    });
}

    /*
    |-------------------------------------------------
    | Rotte per la Gerarchia Organizzativa
    |-------------------------------------------------
    */
    Route::prefix('gerarchia-organizzativa')->name('gerarchia.')->group(function () {
        // Vista principale
        Route::get('/', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'index'])
            ->middleware('permission:gerarchia.view')
            ->name('index');

        // API - Albero
        Route::get('/api/tree', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'getTree'])
            ->name('api.tree');
        Route::get('/api/subtree/{uuid}', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'getSubtree'])
            ->name('api.subtree');
        Route::get('/api/search', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'search'])
            ->name('api.search');

        // API - Tipi
        Route::get('/api/types', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'getTypes'])
            ->name('api.types');
        Route::get('/api/types/containable/{parentUuid?}', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'getContainableTypes'])
            ->name('api.types.containable');

        // API - CRUD Unità
        Route::get('/api/units/{uuid}', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'show'])
            ->name('api.units.show');
        Route::post('/api/units', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'store'])
            ->middleware('permission:gerarchia.edit')
            ->name('api.units.store');
        Route::put('/api/units/{uuid}', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'update'])
            ->middleware('permission:gerarchia.edit')
            ->name('api.units.update');
        Route::post('/api/units/{uuid}/move', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'move'])
            ->middleware('permission:gerarchia.edit')
            ->name('api.units.move');
        Route::post('/api/units/{uuid?}/reorder', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'reorder'])
            ->middleware('permission:gerarchia.edit')
            ->name('api.units.reorder');
        Route::delete('/api/units/{uuid}', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'destroy'])
            ->middleware('permission:gerarchia.delete')
            ->name('api.units.destroy');

        // API - Assegnazioni
        Route::get('/api/units/{uuid}/assignments', [\App\Http\Controllers\OrganizationalHierarchyController::class, 'getAssignments'])
            ->name('api.units.assignments');
    });

    // Rotta parametrica anagrafica (DEVE essere DOPO tutte le altre rotte specifiche)
    // Altrimenti cattura /anagrafica/export-excel, /anagrafica/create, ecc. come ID militare
    Route::get('/anagrafica/{militare}', [MilitareController::class, 'show'])
        ->middleware('permission:anagrafica.view')
        ->name('anagrafica.show');
        
}); // Fine middleware auth

