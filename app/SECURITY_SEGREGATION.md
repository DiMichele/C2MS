# SUGECO: Segregazione Dati per Compagnia - Guida Sicurezza

## Architettura

La segregazione dati è implementata tramite:

1. **Global Scope** (`App\Scopes\CompagniaScope`) - Filtra automaticamente le query Eloquent
2. **Trait** (`App\Traits\BelongsToCompagnia`) - Applica lo scope ai modelli
3. **Policy** (`App\Policies\MilitarePolicy`) - Autorizza le azioni (view/update/delete)
4. **Middleware** (`App\Http\Middleware\EnforceCompagniaAccess`) - Previene manipolazione parametri

## Modelli Protetti

I seguenti modelli usano `BelongsToCompagnia`:
- `Militare`
- `Plotone`
- `BoardActivity`

## ⚠️ RISCHI E LIMITAZIONI

### 1. Il Global Scope NON protegge:

```php
// ❌ PERICOLOSO - Bypassa il Global Scope
DB::table('militari')->where('id', $id)->first();

// ❌ PERICOLOSO - Query raw
DB::select('SELECT * FROM militari WHERE id = ?', [$id]);

// ❌ PERICOLOSO - Job/Command senza autenticazione
// In un Job, Auth::user() è null, quindi il Global Scope blocca tutto
// o peggio, se usi withoutGlobalScope, vedi tutto
```

### 2. Modelli correlati NON scoped

Le seguenti entità **NON** hanno il trait `BelongsToCompagnia`:
- `PianificazioneGiornaliera` (collegata a Militare)
- `TipoServizio` (dati di sistema)
- `Grado`, `Polo`, `Mansione` (anagrafiche comuni)

**Regola**: Accedi sempre a questi dati tramite il modello Militare scoped:
```php
// ✅ CORRETTO - Passa dal militare scoped
$militare = Militare::findOrFail($id);
$pianificazioni = $militare->pianificazioniGiornaliere;

// ❌ PERICOLOSO - Accesso diretto
PianificazioneGiornaliera::where('militare_id', $id)->get();
```

### 3. N+1 su isAcquiredBy()

Il metodo `$militare->isAcquiredBy()` esegue una query ogni volta.

**Soluzione implementata**: In `MilitareService::getFilteredMilitari()` e nel CPT, 
gli attributi `is_owner` e `is_acquired` sono calcolati direttamente nella query con `selectRaw`.

```php
// ✅ CORRETTO - Usa attributo calcolato
$isAcquired = (bool) $militare->is_acquired;

// ⚠️ LENTO - Metodo con query (solo se necessario)
$isAcquired = $militare->isAcquiredBy($user);
```

## Best Practice per Nuovi Endpoint

### 1. Controller - Usa sempre authorize()

```php
public function update(Request $request, $id)
{
    $militare = Militare::findOrFail($id); // Global Scope filtra
    $this->authorize('update', $militare); // Policy decide
    // ...
}
```

### 2. Nuovi Modelli con dati sensibili

```php
class NuovoModello extends Model
{
    use BelongsToCompagnia; // Applica automaticamente lo scope
    
    protected $fillable = ['compagnia_id', ...];
}
```

### 3. Job/Command che devono vedere tutti i dati

```php
// ❌ MAI fare così
$militari = Militare::withoutGlobalScopes()->all();

// ✅ CORRETTO - Autenticati come admin di sistema
Auth::loginUsingId($adminUserId);
$militari = Militare::all();
Auth::logout();

// ✅ ALTERNATIVA - Usa trait withoutCompagniaScope (verifica admin interno)
$militari = Militare::withoutCompagniaScope()->get();
```

### 4. Export/Report

```php
// ✅ CORRETTO - Passa sempre dal modello scoped
$militari = Militare::with('pianificazioniGiornaliere')->get();

// Export rispetta automaticamente la compagnia dell'utente
```

## Test Automatici Raccomandati

```php
// test/Feature/SegregationTest.php

public function test_user_cannot_see_other_company_militari()
{
    $user124 = User::factory()->create(['compagnia_id' => 124]);
    $user125 = User::factory()->create(['compagnia_id' => 125]);
    $militare125 = Militare::factory()->create(['compagnia_id' => 125]);
    
    $this->actingAs($user124);
    
    // Non deve trovare il militare dell'altra compagnia
    $this->assertNull(Militare::find($militare125->id));
}

public function test_acquired_militare_is_read_only()
{
    // Setup: militare 125, attività 124 con quel militare
    // ...
    
    $this->actingAs($user124);
    
    // Può vedere
    $response = $this->get(route('anagrafica.show', $militare125));
    $response->assertOk();
    
    // Non può modificare
    $response = $this->put(route('anagrafica.update', $militare125), [...]);
    $response->assertForbidden();
}
```

## Checklist Code Review

### Checklist Endpoint (Sicurezza)

Per ogni nuovo endpoint che tocca dati sensibili:

- [ ] Usa modelli Eloquent scoped (NO `DB::table()` su tabelle sensibili)
- [ ] `$this->authorize()` sempre presente per azioni di modifica
- [ ] Nessun `withoutGlobalScopes()` se non in contesti admin-only verificati
- [ ] Nessun `where('compagnia_id', ...)` manuale (il Global Scope già filtra)
- [ ] Export/search/report rispettano scope e policy
- [ ] Route model binding usa il modello scoped (es. `Militare $militare`)

### Checklist Performance (N+1)

Per ogni lista di militari:

- [ ] Usa `Militare::withVisibilityFlags()` per avere `is_owner`/`is_acquired` calcolati
- [ ] MAI chiamare `isAcquiredBy()` o `isOwnerFor()` dentro loop
- [ ] Verifica con `EXPLAIN` che gli indici siano usati
- [ ] Eager load relazioni con `with([...])` prima del `get()`

### Checklist Nuovi Modelli

Per ogni nuovo modello con dati sensibili:

- [ ] Usa trait `BelongsToCompagnia`
- [ ] Ha colonna `compagnia_id` con foreign key
- [ ] Ha Policy registrata in `AppServiceProvider`
- [ ] Test automatici verificano segregazione

## Pattern Standard per Liste Militari

```php
// ✅ CORRETTO - Pattern standard
$militari = Militare::withVisibilityFlags()
    ->with(['grado', 'plotone', ...])
    ->orderByGradoENome()
    ->get();

// In Blade:
$isOwner = (bool) ($m->is_owner ?? true);
$isAcquired = (bool) ($m->is_acquired ?? false);
$isReadOnly = !$isOwner;
```

```php
// ❌ SBAGLIATO - Causa N+1
foreach ($militari as $m) {
    $isAcquired = $m->isAcquiredBy($user); // Query per ogni militare!
}
```

```php
// ❌ SBAGLIATO - Taglia fuori gli acquired
$militari = Militare::where('compagnia_id', $user->compagnia_id)->get();
```

