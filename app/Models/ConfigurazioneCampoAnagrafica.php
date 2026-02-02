<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigurazioneCampoAnagrafica extends Model
{
    use HasFactory;

    protected $table = 'configurazione_campi_anagrafica';

    protected $fillable = [
        'organizational_unit_id',
        'nome_campo',
        'etichetta',
        'tipo_campo',
        'opzioni',
        'ordine',
        'attivo',
        'obbligatorio',
        'is_system',
        'descrizione',
    ];

    protected $casts = [
        'opzioni' => 'array',
        'attivo' => 'boolean',
        'obbligatorio' => 'boolean',
        'is_system' => 'boolean',
        'ordine' => 'integer',
    ];

    // Scopes
    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    public function scopeOrdinati($query)
    {
        return $query->orderBy('ordine')->orderBy('etichetta');
    }

    /**
     * Filtra solo i campi di sistema (non eliminabili).
     */
    public function scopeSistemici($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Filtra solo i campi custom (eliminabili).
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Filtra per unità organizzativa (configurazione colonne per unità).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $unitId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUnit($query, ?int $unitId)
    {
        if ($unitId === null) {
            return $query->whereNull('organizational_unit_id');
        }
        return $query->where('organizational_unit_id', $unitId);
    }

    // Relazioni
    public function organizationalUnit()
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    public function valori()
    {
        return $this->hasMany(ValoreCampoAnagrafica::class, 'configurazione_campo_id');
    }

    // Helper per ottenere il valore per un militare specifico
    public function getValorePerMilitare($militare_id)
    {
        return $this->valori()->where('militare_id', $militare_id)->first()?->valore;
    }

    /**
     * Definizione campi di sistema (stessa lista usata in GestioneCampiAnagraficaController).
     *
     * @return array<int, array{nome_campo: string, etichetta: string, tipo_campo: string, ordine: int}>
     */
    public static function getCampiSistemaDefault(): array
    {
        return [
            ['nome_campo' => 'unita_battaglione', 'etichetta' => 'Unità', 'tipo_campo' => 'text', 'ordine' => 0], // Colonna Battaglione (sistema)
            ['nome_campo' => 'compagnia', 'etichetta' => 'Compagnia', 'tipo_campo' => 'select', 'ordine' => 1],
            ['nome_campo' => 'grado', 'etichetta' => 'Grado', 'tipo_campo' => 'select', 'ordine' => 2],
            ['nome_campo' => 'cognome', 'etichetta' => 'Cognome', 'tipo_campo' => 'text', 'ordine' => 3],
            ['nome_campo' => 'nome', 'etichetta' => 'Nome', 'tipo_campo' => 'text', 'ordine' => 4],
            ['nome_campo' => 'plotone', 'etichetta' => 'Plotone', 'tipo_campo' => 'select', 'ordine' => 5],
            ['nome_campo' => 'ufficio', 'etichetta' => 'Ufficio', 'tipo_campo' => 'select', 'ordine' => 6],
            ['nome_campo' => 'incarico', 'etichetta' => 'Incarico', 'tipo_campo' => 'select', 'ordine' => 7],
            ['nome_campo' => 'patenti', 'etichetta' => 'Patenti', 'tipo_campo' => 'text', 'ordine' => 8],
            ['nome_campo' => 'nos', 'etichetta' => 'NOS', 'tipo_campo' => 'select', 'ordine' => 9],
            ['nome_campo' => 'anzianita', 'etichetta' => 'Anzianità', 'tipo_campo' => 'number', 'ordine' => 10],
            ['nome_campo' => 'data_nascita', 'etichetta' => 'Data di Nascita', 'tipo_campo' => 'date', 'ordine' => 11],
            ['nome_campo' => 'email_istituzionale', 'etichetta' => 'Email Istituzionale', 'tipo_campo' => 'email', 'ordine' => 12],
            ['nome_campo' => 'telefono', 'etichetta' => 'Cellulare', 'tipo_campo' => 'tel', 'ordine' => 13],
            ['nome_campo' => 'codice_fiscale', 'etichetta' => 'Codice Fiscale', 'tipo_campo' => 'text', 'ordine' => 14],
            ['nome_campo' => 'istituti', 'etichetta' => 'Istituti', 'tipo_campo' => 'text', 'ordine' => 15],
        ];
    }

    /**
     * Crea i campi di sistema per un'unità se non esistono (evita vista vuota).
     *
     * @param int|null $unitId
     * @return void
     */
    public static function ensureSystemFieldsForUnit(?int $unitId): void
    {
        if ($unitId === null) {
            return;
        }
        foreach (self::getCampiSistemaDefault() as $config) {
            self::firstOrCreate(
                [
                    'nome_campo' => $config['nome_campo'],
                    'organizational_unit_id' => $unitId,
                ],
                [
                    'etichetta' => $config['etichetta'],
                    'tipo_campo' => $config['tipo_campo'],
                    'opzioni' => null,
                    'ordine' => $config['ordine'],
                    'attivo' => true,
                    'obbligatorio' => false,
                    'is_system' => true,
                    'descrizione' => null,
                ]
            );
        }
    }
}
