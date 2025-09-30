# 🧪 C2MS Test Suite

## Panoramica

Suite di test per il **C2MS (Command and Control Management System)** che include test unitari per verificare le funzionalità principali del sistema di gestione militare.

## 📁 Struttura Test

```
tests/
├── TestCase.php                    # Classe base per tutti i test
├── CreatesApplication.php          # Trait per creare l'applicazione  
├── Unit/                          # Test Unitari (5 file)
│   ├── MilitareModelTest.php      # Test modello Militare
│   ├── MilitareServiceTest.php    # Test servizio MilitareService
│   ├── CertificatiServiceTest.php # Test servizio CertificatiService
│   ├── EventoModelTest.php        # Test modello Evento
│   └── GradoModelTest.php         # Test modello Grado
└── README.md                      # Questa documentazione
```

## 🎯 Copertura Test

### Test Unitari Implementati

**MilitareModelTest.php** - 20+ test cases
- ✅ Relazioni Eloquent (grado, plotone, polo, ruolo, mansione)
- ✅ Query Scopes (presenti, assenti, inEvento, orderByGradoENome)
- ✅ Metodi business logic (getNomeCompleto, isPresente, hasAssenzaInDate)
- ✅ Attributi calcolati (media_valutazioni)
- ✅ Gestione directory e file system
- ✅ Certificazioni (hasCertificatiValidi, hasIdoneitaValide)

**MilitareServiceTest.php** - 15+ test cases
- ✅ Filtri avanzati (grado, plotone, presenza)
- ✅ Ricerca intelligente (nome, cognome, iniziali)
- ✅ Gestione valutazioni (create, update, fields)
- ✅ Upload/gestione foto profilo
- ✅ CRUD operations e validazione

**CertificatiServiceTest.php** - 12+ test cases
- ✅ Logica certificazioni lavoratori
- ✅ Gestione idoneità operative  
- ✅ Validazione stati certificati
- ✅ Query building e filtri

**EventoModelTest.php** - 10+ test cases
- ✅ Relazioni con militari
- ✅ Scopes temporali (attivi, futuri, passati)
- ✅ Controlli sovrapposizione date
- ✅ Metodi utilità (durata, stato)

**GradoModelTest.php** - 8+ test cases
- ✅ Ordinamento gerarchico
- ✅ Relazioni con militari
- ✅ Metodi di ricerca
- ✅ Validazione dati

## 🚀 Esecuzione Test

### Script Automatici
```powershell
# Test completi con backup automatico (CONSIGLIATO)
.\run-tests-safe.ps1

# Test standard con coverage
.\run-tests.ps1
```

### Comandi Manuali

```bash
# Tutti i test unitari
vendor/bin/phpunit tests/Unit

# Test specifico con dettagli
vendor/bin/phpunit tests/Unit/MilitareModelTest.php --testdox

# Test con coverage HTML
vendor/bin/phpunit --coverage-html=coverage

# Test con debug verboso
vendor/bin/phpunit --debug tests/Unit/MilitareServiceTest.php
```

### Preparazione Ambiente Test

```bash
# Configura PHPUnit per SQLite in memoria (già configurato)
# Verifica configurazione in phpunit.xml:
# <env name="DB_CONNECTION" value="sqlite"/>
# <env name="DB_DATABASE" value=":memory:"/>

# Verifica dipendenze test
composer install --dev
```

## 📊 Configurazione Test

### Database Test Sicuro
Il sistema è configurato per utilizzare SQLite in memoria durante i test:
- **Isolamento completo**: Nessun impatto sul database di produzione
- **Performance**: Test rapidi senza I/O su disco  
- **Pulizia automatica**: Database ricreato ad ogni test run

### TestCase Base
La classe `TestCase` fornisce:
- 🗄️ **Database refresh** automatico (SQLite in memoria)
- 👤 **Seed dati base** solo per test (gradi, plotoni, poli)
- 🏭 **Factory methods** ottimizzati per test
- 📅 **Date helpers** per scenari temporali
- 🔧 **Utility methods** per assertions

### Script di Sicurezza
`run-tests-safe.ps1` include:
- Verifica configurazione SQLite
- Backup automatico database produzione
- Controlli pre-test
- Ripristino in caso di problemi

## 🧬 Pattern di Test

### AAA Pattern (Arrange-Act-Assert)
```php
public function test_militare_creation(): void
{
    // Arrange - Prepara dati
    $grado = Grado::factory()->create();
    $data = ['nome' => 'Mario', 'cognome' => 'Rossi', 'grado_id' => $grado->id];
    
    // Act - Esegui azione
    $militare = Militare::create($data);
    
    // Assert - Verifica risultato
    $this->assertEquals('Mario', $militare->nome);
    $this->assertDatabaseHas('militari', $data);
}
```

### Test di Relazioni
```php
public function test_militare_relationships(): void
{
    $militare = Militare::factory()->create();
    
    // Verifica che le relazioni siano caricate correttamente
    $this->assertInstanceOf(Grado::class, $militare->grado);
    $this->assertInstanceOf(Plotone::class, $militare->plotone);
}
```

## 🛠️ Strumenti e Tecnologie

- **PHPUnit 11.x** - Framework test principale
- **Laravel Testing** - Utilities per test Laravel
- **SQLite in Memory** - Database test isolato
- **Faker** - Generazione dati test realistici
- **Factory Pattern** - Creazione modelli per test

## 🔍 Debug e Troubleshooting

### Test Falliti
```bash
# Esegui con stop al primo errore
vendor/bin/phpunit --stop-on-failure tests/Unit/

# Debug specifico test
vendor/bin/phpunit --debug tests/Unit/MilitareModelTest.php::test_militare_creation
```

### Database Test Issues
```bash
# Verifica configurazione SQLite
php -m | grep sqlite

# Test connessione in memoria
vendor/bin/phpunit tests/Unit/GradoModelTest.php -v
```

### Performance Test
```bash
# Misurazione tempi
vendor/bin/phpunit tests/Unit/ --debug --verbose
```

## 📈 Metriche Test

### Target Coverage Attuali
- **Modelli Core**: > 90% (Militare, Grado, Evento)
- **Servizi**: > 85% (MilitareService, CertificatiService)  
- **Business Logic**: > 80%

### Performance Benchmark
- **Suite completa**: < 30 secondi
- **Test unitari**: < 15 secondi
- **Test singolo**: < 2 secondi

## 🚨 Note Importanti

### Sicurezza Test
- ✅ Database test completamente isolato (SQLite in memoria)
- ✅ Nessun impatto su dati di produzione
- ✅ Backup automatico nel script safe
- ✅ Configurazione verificata automaticamente

### Limitazioni Attuali
- ❌ **Feature tests HTTP non implementati** (solo unit tests)
- ❌ Test di integrazione API da sviluppare
- ❌ Test browser automatizzati non presenti

## 📝 Prossimi Sviluppi

- [ ] **Feature Tests**: Test HTTP endpoint
- [ ] **Integration Tests**: Test completi flussi
- [ ] **Browser Tests**: Test UI automatizzati
- [ ] **Performance Tests**: Test sotto carico
- [ ] **API Tests**: Test endpoint REST

---

**Suite Test C2MS v2.1.0** - *Test sicuri e affidabili per il sistema di gestione militare* 