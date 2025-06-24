# 🧪 C2MS Test Suite

## Panoramica

Questa Test Suite completa per il **C2MS (Command and Control Management System)** include test unitari e feature per verificare tutte le funzionalità principali del sistema di gestione militare.

## 📁 Struttura Test

```
tests/
├── TestCase.php                    # Classe base per tutti i test
├── CreatesApplication.php          # Trait per creare l'applicazione
├── Unit/                          # Test Unitari
│   ├── MilitareModelTest.php      # Test modello Militare
│   ├── MilitareServiceTest.php    # Test servizio MilitareService
│   └── AssenzaModelTest.php       # Test modello Assenza
├── Feature/                       # Test Feature (End-to-End)
│   ├── MilitareControllerTest.php # Test controller HTTP
│   └── DashboardControllerTest.php # Test dashboard e API
└── README.md                      # Questa documentazione
```

## 🎯 Copertura Test

### Test Unitari (Unit Tests)

**MilitareModelTest.php** - 20+ test cases
- ✅ Relazioni Eloquent (grado, plotone, polo, ruolo, mansione)
- ✅ Query Scopes (presenti, assenti, inEvento, orderByGradoENome)
- ✅ Metodi business logic (getNomeCompleto, isPresente, hasAssenzaInDate)
- ✅ Attributi calcolati (media_valutazioni)
- ✅ Gestione directory e file system

**MilitareServiceTest.php** - 15+ test cases
- ✅ Filtri avanzati (grado, plotone, presenza)
- ✅ Ricerca intelligente (nome, cognome, iniziali)
- ✅ Gestione valutazioni (create, update, fields)
- ✅ Upload/gestione foto profilo
- ✅ CRUD operations e validazione

**AssenzaModelTest.php** - 12+ test cases
- ✅ Scopes temporali (attiveOggi, future, passate)
- ✅ Controlli sovrapposizione date
- ✅ Metodi utilità (durata, stato, tipo)
- ✅ Casting automatico date Carbon

### Test Feature (Integration Tests)

**MilitareControllerTest.php** - 18+ test cases
- ✅ CRUD completo HTTP (index, show, create, store, edit, update, destroy)
- ✅ API endpoints (/api/militari, ricerca AJAX)
- ✅ Upload/gestione foto via HTTP
- ✅ Filtri e paginazione web
- ✅ Gestione errori e validazione
- ✅ Aggiornamenti real-time

**DashboardControllerTest.php** - 12+ test cases
- ✅ Statistiche accurate (presenti, assenti, eventi)
- ✅ Performance con large dataset (100+ militari)
- ✅ Certificati in scadenza
- ✅ Eventi futuri e filtri temporali
- ✅ API JSON per dashboard
- ✅ Gestione dati corrotti/anomali
- ✅ Cache e ottimizzazioni

## 🚀 Esecuzione Test

### Quick Start
```powershell
# Esegui script automatico (consigliato)
.\run-tests.ps1
```

### Comandi Manuali

```bash
# Tutti i test con coverage
vendor/bin/phpunit --coverage-html=coverage

# Solo test unitari
vendor/bin/phpunit tests/Unit

# Solo test feature
vendor/bin/phpunit tests/Feature

# Test specifico
vendor/bin/phpunit tests/Unit/MilitareModelTest.php

# Con debug verbose
vendor/bin/phpunit --debug tests/Feature/MilitareControllerTest.php
```

### Preparazione Ambiente

```bash
# Installa dipendenze
composer install

# Configura ambiente test
cp .env.example .env.testing
php artisan key:generate --env=testing

# Migra database test
php artisan migrate:fresh --env=testing
```

## 📊 Metriche e Coverage

### Target Coverage
- **Modelli**: > 95% line coverage
- **Servizi**: > 90% line coverage  
- **Controller**: > 85% line coverage
- **API**: > 90% line coverage

### Performance Benchmarks
- **Suite completa**: < 60 secondi
- **Test unitari**: < 20 secondi
- **Test feature**: < 40 secondi
- **Dashboard con 100+ militari**: < 2 secondi

## 🧬 Architettura Test

### TestCase Base
La classe `TestCase` fornisce:
- 🗄️ **Database refresh** automatico per ogni test
- 👤 **Seed dati base** (gradi, plotoni, poli, ruoli, mansioni)
- 🏭 **Factory methods** per creare militari di test
- 📅 **Date helpers** per test temporali
- 🔧 **Utility methods** per assertions comuni

### Pattern Utilizzati

**AAA Pattern** (Arrange-Act-Assert)
```php
public function test_militare_creation(): void
{
    // Arrange
    $data = ['nome' => 'Mario', 'cognome' => 'Rossi'];
    
    // Act
    $militare = $this->createTestMilitare($data);
    
    // Assert
    $this->assertEquals('Mario', $militare->nome);
    $this->assertDatabaseHas('militari', $data);
}
```

**Given-When-Then** per feature tests
```php
public function test_militare_search(): void
{
    // Given - militari esistenti
    $this->createTestMilitare(['nome' => 'Mario']);
    
    // When - ricerca eseguita
    $response = $this->get(route('militare.search', ['q' => 'Mar']));
    
    // Then - risultati corretti
    $response->assertJson([['nome' => 'Mario']]);
}
```

## 🛠️ Strumenti e Tecnologie

- **PHPUnit 11.x** - Framework test principale
- **Laravel Testing** - Feature HTTP testing
- **Faker** - Generazione dati test realistici
- **RefreshDatabase** - Isolamento database per test
- **Storage Fake** - Mock file system per upload test
- **Carbon** - Manipolazione date per test temporali

## 🔍 Debug e Troubleshooting

### Test Falliti
```bash
# Esegui con stop al primo errore
vendor/bin/phpunit --stop-on-failure

# Debug specifico test
vendor/bin/phpunit --debug tests/Unit/MilitareModelTest.php::test_militare_creation
```

### Database Issues
```bash
# Reset completo database test
php artisan migrate:fresh --env=testing --force

# Verifica connessione
php artisan tinker --env=testing
>>> DB::connection()->getPdo()
```

### Memory/Performance
```bash
# Con memory usage
vendor/bin/phpunit --debug --verbose

# Profiling specifico
php -d memory_limit=512M vendor/bin/phpunit tests/Feature/
```

## 📝 Best Practices

### ✅ DO
- Usa nomi test descrittivi (`test_militare_creation_with_valid_data`)
- Testa un comportamento per test
- Usa factory/helper methods per setup
- Verifica sia stato database che comportamento
- Mock dipendenze esterne (storage, cache, API)

### ❌ DON'T
- Non testare funzionalità Laravel core
- Non condividere stato tra test
- Non usare sleep() per timing
- Non lasciare test skip/incomplete in produzione
- Non hardcodare ID/valori specifici

## 🔄 Integrazione Continua

Il file `run-tests.ps1` può essere integrato in pipeline CI/CD:

```yaml
# GitHub Actions example
- name: Run C2MS Test Suite
  run: |
    composer install --no-dev --optimize-autoloader
    powershell -File run-tests.ps1
    
- name: Upload Coverage
  uses: codecov/codecov-action@v1
  with:
    file: ./coverage/clover.xml
```

## 📈 Espansioni Future

### Test Aggiuntivi Pianificati
- 🔐 **Security Tests** - Autorizzazione e protezione CSRF
- 📱 **API Tests** - Rest API completo con autenticazione
- 🌐 **Browser Tests** - Laravel Dusk per UI testing
- ⚡ **Load Tests** - Performance con 1000+ militari
- 🔄 **Integration Tests** - Integrazione sistemi esterni

### Miglioramenti
- 📊 **Real-time Coverage** reporting
- 🤖 **Auto-test** su file changes
- 📧 **Email notifications** per fallimenti
- 🏆 **Quality gates** per deploy

---

## 🎯 Risultati Attesi

Eseguendo questa Test Suite dovresti ottenere:

- ✅ **65+ test cases** tutti passati
- 📊 **>90% code coverage** sui componenti core
- ⚡ **<60s execution time** per suite completa
- 🛡️ **Confidence** per deploy sicuri
- 📈 **Quality metrics** per continuous improvement

---

**Nota**: Questa Test Suite è progettata per essere eseguita in ambiente di sviluppo con database dedicato per i test. Non eseguire mai in produzione. 