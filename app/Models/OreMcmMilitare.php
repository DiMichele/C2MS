<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modello per tracciare le ore MCM svolte dai militari.
 * 
 * MCM richiede un totale di 40 ore per essere completato.
 * Ore giornaliere:
 * - Lunedì-Giovedì: 8 ore
 * - Venerdì: 4 ore
 * 
 * @property int $id
 * @property int $militare_id
 * @property int|null $teatro_operativo_id
 * @property \Carbon\Carbon $data
 * @property int $ore
 * @property string $stato (pianificato, completato, annullato)
 * @property string|null $note
 * @property int|null $created_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Militare $militare
 * @property-read \App\Models\TeatroOperativo|null $teatroOperativo
 * @property-read \App\Models\User|null $creatore
 */
class OreMcmMilitare extends Model
{
    use HasFactory;

    /**
     * Ore totali richieste per completare MCM
     */
    const ORE_TOTALI_RICHIESTE = 40;

    /**
     * Ore per giorno della settimana
     */
    const ORE_LUNEDI_GIOVEDI = 8;
    const ORE_VENERDI = 4;

    /**
     * Stati possibili
     */
    const STATO_PIANIFICATO = 'pianificato';
    const STATO_COMPLETATO = 'completato';
    const STATO_ANNULLATO = 'annullato';

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'ore_mcm_militari';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'militare_id',
        'teatro_operativo_id',
        'data',
        'ore',
        'stato',
        'note',
        'created_by'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'data' => 'date',
        'ore' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Militare associato
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Teatro operativo associato
     */
    public function teatroOperativo()
    {
        return $this->belongsTo(TeatroOperativo::class);
    }

    /**
     * Utente che ha creato il record
     */
    public function creatore()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per record pianificati
     */
    public function scopePianificati($query)
    {
        return $query->where('stato', self::STATO_PIANIFICATO);
    }

    /**
     * Scope per record completati
     */
    public function scopeCompletati($query)
    {
        return $query->where('stato', self::STATO_COMPLETATO);
    }

    /**
     * Scope per record attivi (non annullati)
     */
    public function scopeAttivi($query)
    {
        return $query->where('stato', '!=', self::STATO_ANNULLATO);
    }

    /**
     * Scope per un militare specifico
     */
    public function scopePerMilitare($query, $militareId)
    {
        return $query->where('militare_id', $militareId);
    }

    /**
     * Scope per un teatro specifico
     */
    public function scopePerTeatro($query, $teatroId)
    {
        return $query->where('teatro_operativo_id', $teatroId);
    }

    // ==========================================
    // METODI STATICI
    // ==========================================

    /**
     * Calcola le ore per una data specifica in base al giorno della settimana
     * 
     * @param Carbon|string $data La data da verificare
     * @return int Le ore per quella giornata (8 lun-gio, 4 ven, 0 weekend)
     */
    public static function calcolaOrePerData($data): int
    {
        $carbonData = $data instanceof Carbon ? $data : Carbon::parse($data);
        $giornoSettimana = $carbonData->dayOfWeek; // 0 = domenica, 1 = lunedì, ..., 5 = venerdì, 6 = sabato

        // Lunedì (1) - Giovedì (4): 8 ore
        if ($giornoSettimana >= 1 && $giornoSettimana <= 4) {
            return self::ORE_LUNEDI_GIOVEDI;
        }
        
        // Venerdì (5): 4 ore
        if ($giornoSettimana === 5) {
            return self::ORE_VENERDI;
        }

        // Weekend: 0 ore (non dovrebbe essere pianificato)
        return 0;
    }

    /**
     * Calcola le ore totali svolte da un militare (pianificate + completate)
     * 
     * @param int $militareId ID del militare
     * @param int|null $teatroId ID del teatro operativo (opzionale)
     * @return int Ore totali
     */
    public static function getOreTotaliMilitare(int $militareId, ?int $teatroId = null): int
    {
        $query = self::attivi()->perMilitare($militareId);
        
        if ($teatroId) {
            $query->perTeatro($teatroId);
        }
        
        return $query->sum('ore');
    }

    /**
     * Calcola le ore rimanenti per completare MCM
     * 
     * @param int $militareId ID del militare
     * @param int|null $teatroId ID del teatro operativo (opzionale)
     * @return int Ore rimanenti (minimo 0)
     */
    public static function getOreRimanentiMilitare(int $militareId, ?int $teatroId = null): int
    {
        $oreSvolte = self::getOreTotaliMilitare($militareId, $teatroId);
        return max(0, self::ORE_TOTALI_RICHIESTE - $oreSvolte);
    }

    /**
     * Verifica se un militare ha completato MCM
     * 
     * @param int $militareId ID del militare
     * @param int|null $teatroId ID del teatro operativo (opzionale)
     * @return bool True se ha completato le 40 ore
     */
    public static function haCompletatoMcm(int $militareId, ?int $teatroId = null): bool
    {
        return self::getOreTotaliMilitare($militareId, $teatroId) >= self::ORE_TOTALI_RICHIESTE;
    }

    /**
     * Calcola la percentuale di completamento MCM
     * 
     * @param int $militareId ID del militare
     * @param int|null $teatroId ID del teatro operativo (opzionale)
     * @return int Percentuale (0-100)
     */
    public static function getPercentualeCompletamento(int $militareId, ?int $teatroId = null): int
    {
        $oreSvolte = self::getOreTotaliMilitare($militareId, $teatroId);
        return min(100, (int) round(($oreSvolte / self::ORE_TOTALI_RICHIESTE) * 100));
    }

    /**
     * Ottiene i dettagli delle ore MCM per un militare
     * 
     * @param int $militareId ID del militare
     * @param int|null $teatroId ID del teatro operativo (opzionale)
     * @return array Dettagli con ore svolte, rimanenti, percentuale e sessioni
     */
    public static function getDettagliMcmMilitare(int $militareId, ?int $teatroId = null): array
    {
        $query = self::attivi()->perMilitare($militareId)->orderBy('data');
        
        if ($teatroId) {
            $query->perTeatro($teatroId);
        }
        
        $sessioni = $query->get();
        $oreSvolte = $sessioni->sum('ore');
        $oreRimanenti = max(0, self::ORE_TOTALI_RICHIESTE - $oreSvolte);
        $percentuale = min(100, (int) round(($oreSvolte / self::ORE_TOTALI_RICHIESTE) * 100));

        return [
            'ore_svolte' => $oreSvolte,
            'ore_rimanenti' => $oreRimanenti,
            'ore_totali_richieste' => self::ORE_TOTALI_RICHIESTE,
            'percentuale' => $percentuale,
            'completato' => $oreSvolte >= self::ORE_TOTALI_RICHIESTE,
            'sessioni' => $sessioni->map(function($s) {
                return [
                    'id' => $s->id,
                    'data' => $s->data->format('d/m/Y'),
                    'data_raw' => $s->data->format('Y-m-d'),
                    'giorno_settimana' => $s->data->locale('it')->dayName,
                    'ore' => $s->ore,
                    'stato' => $s->stato,
                    'note' => $s->note
                ];
            })->toArray(),
            'numero_sessioni' => $sessioni->count()
        ];
    }

    /**
     * Calcola le ore totali per un set di date
     * 
     * @param array $date Array di date (strings o Carbon)
     * @return int Ore totali
     */
    public static function calcolaOreTotaliPerDate(array $date): int
    {
        $oreTotali = 0;
        foreach ($date as $data) {
            $oreTotali += self::calcolaOrePerData($data);
        }
        return $oreTotali;
    }

    /**
     * Verifica se un militare ha già una sessione MCM per una data
     * 
     * @param int $militareId ID del militare
     * @param Carbon|string $data La data da verificare
     * @return bool True se esiste già una sessione
     */
    public static function haSessionePerData(int $militareId, $data): bool
    {
        $carbonData = $data instanceof Carbon ? $data : Carbon::parse($data);
        
        return self::where('militare_id', $militareId)
            ->whereDate('data', $carbonData)
            ->where('stato', '!=', self::STATO_ANNULLATO)
            ->exists();
    }

    // ==========================================
    // METODI DI ISTANZA
    // ==========================================

    /**
     * Completa la sessione MCM
     */
    public function completa(): bool
    {
        $this->stato = self::STATO_COMPLETATO;
        return $this->save();
    }

    /**
     * Annulla la sessione MCM
     */
    public function annulla(): bool
    {
        $this->stato = self::STATO_ANNULLATO;
        return $this->save();
    }

    /**
     * Verifica se la sessione è pianificata
     */
    public function isPianificata(): bool
    {
        return $this->stato === self::STATO_PIANIFICATO;
    }

    /**
     * Verifica se la sessione è completata
     */
    public function isCompletata(): bool
    {
        return $this->stato === self::STATO_COMPLETATO;
    }

    /**
     * Verifica se la sessione è annullata
     */
    public function isAnnullata(): bool
    {
        return $this->stato === self::STATO_ANNULLATO;
    }

    /**
     * Ottiene il label dello stato
     */
    public function getStatoLabel(): string
    {
        return match($this->stato) {
            self::STATO_PIANIFICATO => 'Pianificato',
            self::STATO_COMPLETATO => 'Completato',
            self::STATO_ANNULLATO => 'Annullato',
            default => $this->stato
        };
    }

    /**
     * Ottiene la classe CSS per lo stato
     */
    public function getStatoCssClass(): string
    {
        return match($this->stato) {
            self::STATO_PIANIFICATO => 'bg-primary',
            self::STATO_COMPLETATO => 'bg-success',
            self::STATO_ANNULLATO => 'bg-secondary',
            default => 'bg-light'
        };
    }

    /**
     * Ottiene il giorno della settimana formattato
     */
    public function getGiornoSettimana(): string
    {
        return $this->data->locale('it')->dayName;
    }
}
