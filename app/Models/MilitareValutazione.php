<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modello per le valutazioni dei militari
 * 
 * NOTA: La tabella militare_valutazioni potrebbe non esistere nel database.
 * È stata rimossa in una migrazione di pulizia. Prima di usare questo model,
 * verificare che la tabella esista.
 * 
 * @property int $id
 * @property int $militare_id
 * @property int|null $professionalita
 * @property int|null $affidabilita
 * @property int|null $puntualita
 * @property int|null $impegno
 * @property int|null $autonomia
 * @property string|null $note_positive
 * @property string|null $note_negative
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Militare $militare
 */
class MilitareValutazione extends Model
{
    use HasFactory;

    protected $table = 'militare_valutazioni';

    protected $fillable = [
        'militare_id',
        'professionalita',
        'affidabilita',
        'puntualita',
        'impegno',
        'autonomia',
        'note_positive',
        'note_negative',
    ];

    protected $casts = [
        'professionalita' => 'integer',
        'affidabilita' => 'integer',
        'puntualita' => 'integer',
        'impegno' => 'integer',
        'autonomia' => 'integer',
    ];

    /**
     * Relazione con il militare
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Verifica se la tabella esiste nel database
     */
    public static function tableExists(): bool
    {
        return \Schema::hasTable('militare_valutazioni');
    }
}
