<?php

namespace App\Services;

use App\Models\Militare;
use App\Models\CertificatiLavoratori;
use App\Models\Idoneita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * 
 * Service per la gestione dei certificati lavoratori.
 * Centralizza la logica di business per certificati, filtri, file upload,
 * validazioni e sistema di cache.
 * 
 * @package C2MS
 * @subpackage Services
 * @version 1.0
 * @since 1.0
 * @author Michele Di Gennaro
 * @copyright 2025 C2MS
 */
class CertificatiService
{
    /**
     * Tipi di certificati supportati
     */
    public const TIPI_CORSI_LAVORATORI = [
        'corsi_lavoratori_4h', 
        'corsi_lavoratori_8h', 
        'corsi_lavoratori_preposti', 
        'corsi_lavoratori_dirigenti'
    ];
    
    public const TIPI_IDONEITA = [
        'idoneita_mansione', 
        'idoneita_smi', 
        'idoneita'
    ];

    /**
     * Costruisce query per militari con certificati
     * 
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildMilitariQuery(Request $request)
    {
        $query = Militare::query();
        
        // Eager loading ottimizzato
        $query->with([
            'grado:id,nome,ordine',
        ]);
        
        // Filtro per militare specifico
        if ($request->filled('militare_id')) {
            $query->where('militari.id', $request->input('militare_id'));
        }
        
        // Ordinamento ottimizzato
        $query->orderByGradoENome();
        
        return $query;
    }

    /**
     * Applica filtri per corsi lavoratori
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyCorsiLavoratoriFilters($query, Request $request)
    {
        // Eager loading specifico
        $query->with([
            'certificatiLavoratori' => function($q) {
                $q->select('id', 'militare_id', 'tipo', 'data_ottenimento', 'data_scadenza', 'file_path');
            },
            'ruoloCertificati:id,nome'
        ]);
        
        // Filtro per ruolo
        if ($request->filled('ruolo')) {
            $ruoloNome = $request->input('ruolo');
            $query->whereHas('ruoloCertificati', function($q) use ($ruoloNome) {
                $q->where('nome', $ruoloNome);
            });
        }
        
        // Applica filtri generici
        $this->applyGenericFilters($query, $request, self::TIPI_CORSI_LAVORATORI, 'certificatiLavoratori');
        
        // Filtri per singoli tipi di certificati
        $this->applySpecificCertificateFilters($query, $request, 'certificatiLavoratori');
        
        return $query;
    }

    /**
     * Applica filtri per idoneità
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyIdoneitaFilters($query, Request $request)
    {
        // Eager loading specifico
        $query->with([
            'idoneita' => function($q) {
                $q->select('id', 'militare_id', 'tipo', 'data_ottenimento', 'data_scadenza', 'file_path');
            },
            'mansione:id,nome'
        ]);
        
        // Filtro per mansione
        if ($request->filled('mansione')) {
            $mansioneName = $request->input('mansione');
            $query->whereHas('mansione', function($q) use ($mansioneName) {
                $q->where('nome', $mansioneName);
            });
        }
        
        // Applica filtri generici
        $this->applyGenericFilters($query, $request, self::TIPI_IDONEITA, 'idoneita');
        
        // Filtri per singoli tipi di idoneità
        foreach (self::TIPI_IDONEITA as $tipo) {
            if ($request->filled($tipo)) {
                $status = $request->input($tipo);
                $this->filterByCertificatoStatus($query, $tipo, $status, 'idoneita');
            }
        }
        
        return $query;
    }

    /**
     * Applica filtri generici comuni a entrambi i tipi di certificati
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @param array $tipi
     * @param string $relation
     */
    protected function applyGenericFilters($query, Request $request, array $tipi, string $relation)
    {
        if ($request->filled('certificati_registrati')) {
            $this->filterByRegistrati($query, $request->input('certificati_registrati'), $tipi, $relation);
        }
        
        if ($request->filled('stato_file')) {
            $this->filterByStatoFile($query, $request->input('stato_file'), $tipi, $relation);
        }
        
        if ($request->filled('valido')) {
            $this->filterByValidita($query, $request->input('valido'), $tipi, $relation);
        }
        
        if ($request->filled('in_scadenza')) {
            $this->filterByInScadenza($query, $request->input('in_scadenza'), $tipi, $relation);
        }
        
        if ($request->filled('scaduti')) {
            $this->filterByScaduti($query, $request->input('scaduti'), $tipi, $relation);
        }
    }

    /**
     * Applica filtri specifici per certificati lavoratori
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @param string $relation
     */
    protected function applySpecificCertificateFilters($query, Request $request, string $relation)
    {
        foreach (['4h', '8h', 'preposti', 'dirigenti'] as $tipo) {
            $paramName = "cert_$tipo";
            if ($request->filled($paramName)) {
                $certTipo = "corsi_lavoratori_$tipo";
                $status = $request->input($paramName);
                $this->filterByCertificatoStatus($query, $certTipo, $status, $relation);
            }
        }
    }

    /**
     * Trova un certificato per ID
     * 
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findCertificato($id)
    {
        // Cerca prima nei certificati lavoratori
        $certificato = CertificatiLavoratori::find($id);
        if ($certificato) {
            return $certificato;
        }
        
        // Poi nelle idoneità
        $certificato = Idoneita::find($id);
        if ($certificato) {
            return $certificato;
        }
        
        throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Certificato non trovato');
    }

    /**
     * Ottiene la classe del modello per tipo certificato
     * 
     * @param string $tipo
     * @return string
     */
    public function getCertificatoModelClass($tipo)
    {
        if (in_array($tipo, self::TIPI_CORSI_LAVORATORI)) {
            return CertificatiLavoratori::class;
        }
        
        return Idoneita::class;
    }

    /**
     * Ottiene la route di redirect per tipo certificato
     * 
     * @param string $tipo
     * @return string
     */
    public function getRedirectRoute($tipo)
    {
        if (in_array($tipo, self::TIPI_CORSI_LAVORATORI)) {
            return 'certificati.corsi-lavoratori';
        }
        
        return 'certificati.idoneita';
    }

    /**
     * Salva un file certificato
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param \App\Models\Militare $militare
     * @param string $tipo
     * @return array
     */
    public function saveFile($file, $militare, $tipo)
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        
        // Determina la cartella di destinazione in base al tipo
        if (in_array($tipo, self::TIPI_CORSI_LAVORATORI)) {
            $folderPath = $militare->getCertificatiLavoratoriPath();
        } else {
            $folderPath = $militare->getIdoneitaPath();
        }
        
        // Assicurati che la cartella esista
        Storage::disk('public')->makeDirectory($folderPath);
        
        $path = $file->storeAs($folderPath, $fileName, 'public');
        
        return [
            'path' => $path,
            'filename' => $fileName
        ];
    }

    /**
     * Elimina un file certificato
     * 
     * @param string $filePath
     * @return bool
     */
    public function deleteFile($filePath)
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->delete($filePath);
        }
        
        return true;
    }

    /**
     * Invalida la cache dei certificati
     */
    public function invalidateCache()
    {
        $cacheKeys = [
            'certificati_lavoratori_*',
            'idoneita_*',
            'militari_certificati_*'
        ];
        
        foreach ($cacheKeys as $pattern) {
            Cache::forget($pattern);
        }
    }

    // Metodi di filtro privati (spostati dal controller)
    
    /**
     * Filtro per certificati registrati
     */
    protected function filterByRegistrati($query, $value, $tipi, $relation)
    {
        $query->where(function($q) use ($value, $tipi, $relation) {
            if ($value === '1') {
                // Tutti i certificati sono registrati
                foreach ($tipi as $tipo) {
                    $q->whereHas($relation, function($sq) use ($tipo) {
                        $sq->where('tipo', $tipo);
                    });
                }
            } else {
                // Almeno un certificato mancante
                $q->where(function($sq) use ($tipi, $relation) {
                    foreach ($tipi as $tipo) {
                        $sq->orWhereDoesntHave($relation, function($ssq) use ($tipo) {
                            $ssq->where('tipo', $tipo);
                        });
                    }
                });
            }
        });
        
        return $query;
    }
    
    /**
     * Filtro per stato dei file
     */
    protected function filterByStatoFile($query, $value, $tipi, $relation)
    {
        if ($value === '1') { // File mancanti
            $query->where(function($q) use ($tipi, $relation) {
                foreach ($tipi as $tipo) {
                    $q->orWhereHas($relation, function($sq) use ($tipo) {
                        $sq->where('tipo', $tipo)
                          ->whereNull('file_path');
                    });
                }
            });
        } else { // File presenti
            $query->where(function($q) use ($tipi, $relation) {
                foreach ($tipi as $tipo) {
                    $q->orWhereHas($relation, function($sq) use ($tipo) {
                        $sq->where('tipo', $tipo)
                          ->whereNotNull('file_path');
                    });
                }
            });
        }
        
        return $query;
    }
    
    /**
     * Filtro per validità certificati
     */
    protected function filterByValidita($query, $value, $tipi, $relation)
    {
        $now = now();
        
        if ($value === '1') { // Validi
            $query->where(function($q) use ($tipi, $relation, $now) {
                foreach ($tipi as $tipo) {
                    $q->orWhereHas($relation, function($sq) use ($tipo, $now) {
                        $sq->where('tipo', $tipo)
                          ->where(function($ssq) use ($now) {
                              $ssq->whereNull('data_scadenza')
                                  ->orWhere('data_scadenza', '>', $now);
                          });
                    });
                }
            });
        } else { // Non validi
            $query->where(function($q) use ($tipi, $relation, $now) {
                foreach ($tipi as $tipo) {
                    $q->orWhereHas($relation, function($sq) use ($tipo, $now) {
                        $sq->where('tipo', $tipo)
                          ->where('data_scadenza', '<=', $now);
                    });
                }
            });
        }
        
        return $query;
    }
    
    /**
     * Filtro per certificati in scadenza
     */
    protected function filterByInScadenza($query, $value, $tipi, $relation)
    {
        $now = now();
        $inScadenza = $now->copy()->addDays(30);
        
        if ($value === '1') { // In scadenza
            $query->where(function($q) use ($tipi, $relation, $now, $inScadenza) {
                foreach ($tipi as $tipo) {
                    $q->orWhereHas($relation, function($sq) use ($tipo, $now, $inScadenza) {
                        $sq->where('tipo', $tipo)
                          ->whereBetween('data_scadenza', [$now, $inScadenza]);
                    });
                }
            });
        }
        
        return $query;
    }
    
    /**
     * Filtro per certificati scaduti
     */
    protected function filterByScaduti($query, $value, $tipi, $relation)
    {
        $now = now();
        
        if ($value === '1') { // Scaduti
            $query->where(function($q) use ($tipi, $relation, $now) {
                foreach ($tipi as $tipo) {
                    $q->orWhereHas($relation, function($sq) use ($tipo, $now) {
                        $sq->where('tipo', $tipo)
                          ->where('data_scadenza', '<', $now);
                    });
                }
            });
        }
        
        return $query;
    }
    
    /**
     * Filtro per stato specifico di un certificato
     */
    protected function filterByCertificatoStatus($query, $tipo, $status, $relation)
    {
        $now = now();
        
        switch ($status) {
            case 'presente':
                $query->whereHas($relation, function($q) use ($tipo) {
                    $q->where('tipo', $tipo);
                });
                break;
                
            case 'mancante':
                $query->whereDoesntHave($relation, function($q) use ($tipo) {
                    $q->where('tipo', $tipo);
                });
                break;
                
            case 'valido':
                $query->whereHas($relation, function($q) use ($tipo, $now) {
                    $q->where('tipo', $tipo)
                      ->where(function($sq) use ($now) {
                          $sq->whereNull('data_scadenza')
                              ->orWhere('data_scadenza', '>', $now);
                      });
                });
                break;
                
            case 'scaduto':
                $query->whereHas($relation, function($q) use ($tipo, $now) {
                    $q->where('tipo', $tipo)
                      ->where('data_scadenza', '<', $now);
                });
                break;
                
            case 'in_scadenza':
                $inScadenza = $now->copy()->addDays(30);
                $query->whereHas($relation, function($q) use ($tipo, $now, $inScadenza) {
                    $q->where('tipo', $tipo)
                      ->whereBetween('data_scadenza', [$now, $inScadenza]);
                });
                break;
        }
        
        return $query;
    }
} 
