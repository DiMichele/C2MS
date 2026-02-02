---
name: Fix Gerarchia 500 e Struttura
overview: "Risolvere l'errore 500 sull'API dettagli unità (causa: accesso a type null in getBreadcrumb) e allineare la struttura mostrata alla gerarchia 11° Reggimento Trasmissioni (eseguendo la migrazione/seeder corretta)."
todos:
  - id: todo-1769792807357-o1w47hf5j
    content: ""
    status: pending
isProject: false
---

# Fix Gerarchia Organizzativa: 500 API e Struttura Errata

## 1. Errore 500 su `GET /gerarchia-organizzativa/api/units/{uuid}`

**Causa individuata:** In [app/Models/OrganizationalUnit.php](app/Models/OrganizationalUnit.php) il metodo `getBreadcrumb()` (riga 386-394) usa `$unit->type->name` senza null-check. Se un'unità ha `type_id` null o punta a un tipo eliminato/inesistente, `$unit->type` è null e l'accesso a `->name` solleva un'eccezione, restituendo 500.

**Flusso:** `OrganizationalHierarchyController::show($uuid)` carica l'unità, poi chiama `$unit->getBreadcrumb()` e `$this->hierarchyService->getUnitStats($unit)`. L'eccezione avviene in uno di questi due punti; il breadcrumb è il candidato più probabile per `type` null.

**Interventi:**

- In **OrganizationalUnit::getBreadcrumb()**: sostituire `$unit->type->name ?? null` con `$unit->type?->name ?? null` per evitare "Trying to get property 'name' of null".
- Verificare altri accessi a `$this->type` o `$unit->type` nello stesso file (es. righe 477, 524-527) e proteggere con null-safe dove il tipo può essere assente (es. unità create prima dei tipi o tipo eliminato).

## 2. Struttura diversa da quella richiesta (11° Reggimento Trasmissioni)

**Situazione:** In schermata compare ancora "5° Reggimento Alpini (REG)" con battaglioni Morbegno/Tirano, Ufficio Logistico, CCSL, Infermeria, ecc. La struttura desiderata è invece: **11° Reggimento Trasmissioni** con Comando di Reggimento (4 uffici), Battaglione Leonessa (110, 124, 127), Battaglione Tonale (137, 140, 154), CCSL (6 plotoni), Comando alla Sede.

**Causa:** I dati in DB sono stati creati con una versione precedente del seeder o con il comando `hierarchy:migrate` prima degli aggiornamenti. Il seeder [database/seeders/OrganizationalHierarchySeeder.php](database/seeders/OrganizationalHierarchySeeder.php) e il comando [app/Console/Commands/MigrateToHierarchy.php](app/Console/Commands/MigrateToHierarchy.php) sono già stati adattati alla struttura 11° Reggimento (REG11, Leonessa, Tonale, CCSL, Comando alla Sede), ma non sono stati rieseguiti sulla base dati attuale.

**Interventi:**

- **Opzione A (consigliata se la gerarchia attuale può essere sostituita):** Eseguire la migrazione gerarchia da zero per ricreare la struttura 11°:
- `php artisan hierarchy:migrate --fresh`
- Opzionale: `php artisan hierarchy:migrate --with-assignments` se servono anche le assegnazioni militari.
- **Opzione B:** Se si vuole usare solo il seeder (senza wipe da comando): assicurarsi che `DatabaseSeeder` invochi `OrganizationalHierarchySeeder` e che prima vengano pulite le tabelle `unit_closure`, `unit_assignments`, `organizational_units` (in ordine per FK). Il seeder usa `firstOrCreate(['code' => 'REG11'], ...) `quindi non sovrascrive un root esistente con code `REG`; con DB pulito crea solo REG11 e la struttura 11°.
- **Documentazione:** Aggiungere in README o in un doc operativo la procedura per "allineare la gerarchia al 11° Reggimento Trasmissioni" (es. eseguire `hierarchy:migrate --fresh` e eventuale `--with-assignments`).

## 3. Riepilogo modifiche codice

| File | Modifica |
|------|----------|
| [app/Models/OrganizationalUnit.php](app/Models/OrganizationalUnit.php) | In `getBreadcrumb()` usare `$unit->type?->name ?? null`. Controllare e, se necessario, proteggere con null-safe gli altri accessi a `type` (canMoveTo, toTreeArray, ecc.). |
| Documentazione / README | Breve sezione su come ricreare la struttura 11° (comando `hierarchy:migrate --fresh`). |

Nessuna modifica alle API o alle route: il 500 si risolve rendendo il modello tollerante a `type` null; la struttura si allinea rieseguendo migrazione o seeder già predisposti per il 11° Reggimento.