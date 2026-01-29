<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Compagnia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Controller per la visualizzazione e gestione dei log di audit.
 * 
 * Ottimizzato per gestire grandi volumi di dati (centinaia di migliaia di record)
 * con cache intelligente e query efficienti.
 */
class AuditLogController extends Controller
{
    /**
     * Mostra la lista dei log di audit.
     */
    public function index(Request $request)
    {
        // Verifica permessi - solo admin possono vedere i log
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Non hai i permessi per visualizzare i log di audit.');
        }

        // Query base ottimizzata con select specifico
        $query = AuditLog::query()
            ->select([
                'id', 'user_id', 'user_name', 'action', 'description',
                'entity_type', 'entity_id', 'entity_name', 
                'old_values', 'new_values',
                'compagnia_id', 'status', 'created_at'
            ])
            ->with([
                'user:id,name,username',
                'compagnia:id,nome'
            ])
            ->orderBy('created_at', 'desc');

        // =====================================================================
        // FILTRI (usando scope del model per efficienza)
        // =====================================================================

        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        if ($request->filled('entity_type')) {
            $query->byEntityType($request->entity_type);
        }

        if ($request->filled('compagnia_id')) {
            $query->byCompagnia($request->compagnia_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Ricerca testuale ottimizzata
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (strlen($search) >= 2) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('user_name', 'like', "%{$search}%")
                      ->orWhere('entity_name', 'like', "%{$search}%");
                });
            }
        }

        // Paginazione con configurazione
        $perPage = config('audit.per_page', 50);
        $logs = $query->paginate($perPage)->withQueryString();

        // Dati per i filtri (cachati per 10 minuti)
        $users = Cache::remember('audit_users_list', 600, function () {
            return User::orderBy('name')->get(['id', 'name', 'username']);
        });
        
        $compagnie = Cache::remember('audit_compagnie_list', 600, function () {
            return Compagnia::orderBy('nome')->get(['id', 'nome']);
        });
        
        $actions = AuditLog::ACTION_LABELS;
        $entityTypes = AuditLog::ENTITY_LABELS;

        return view('admin.audit-logs.index', compact(
            'logs',
            'users',
            'compagnie',
            'actions',
            'entityTypes'
        ));
    }

    /**
     * Mostra i dettagli di un singolo log.
     */
    public function show(AuditLog $auditLog)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Non hai i permessi per visualizzare i log di audit.');
        }

        $auditLog->load(['user:id,name,username', 'compagnia:id,nome']);

        return view('admin.audit-logs.show', compact('auditLog'));
    }

    /**
     * Mostra i log di un utente specifico.
     */
    public function userLogs(User $user)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Non hai i permessi per visualizzare i log di audit.');
        }

        $perPage = config('audit.per_page', 50);
        
        $logs = AuditLog::select([
                'id', 'action', 'description', 'entity_type', 
                'entity_name', 'status', 'created_at'
            ])
            ->byUser($user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return view('admin.audit-logs.user', compact('logs', 'user'));
    }

    /**
     * Mostra solo i log di accesso (login/logout).
     */
    public function accessLogs(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Non hai i permessi per visualizzare i log di audit.');
        }

        $perPage = config('audit.per_page', 50);

        $query = AuditLog::select([
                'id', 'user_id', 'user_name', 'action', 
                'description', 'status', 'created_at'
            ])
            ->accessLogs()
            ->with('user:id,name,username')
            ->orderBy('created_at', 'desc');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($perPage)->withQueryString();

        return view('admin.audit-logs.access', compact('logs'));
    }

    /**
     * Esporta i log in CSV con streaming per grandi dataset.
     */
    public function export(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Non hai i permessi per esportare i log.');
        }

        // Query con gli stessi filtri dell'index
        $query = AuditLog::select([
                'id', 'user_name', 'action', 'description',
                'entity_type', 'entity_name', 'status',
                'compagnia_id', 'created_at'
            ])
            ->with('compagnia:id,nome')
            ->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }
        if ($request->filled('action')) {
            $query->byAction($request->action);
        }
        if ($request->filled('entity_type')) {
            $query->byEntityType($request->entity_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Limite configurabile per l'export
        $exportLimit = config('audit.performance.export_batch_size', 1000) * 10;
        $totalCount = $query->count();
        
        // Genera CSV con streaming per efficienza memoria
        $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($query, $exportLimit) {
            $file = fopen('php://output', 'w');
            
            // BOM per Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Intestazioni
            fputcsv($file, [
                'Data/Ora',
                'Utente',
                'Azione',
                'Descrizione',
                'Tipo Dato',
                'Nome Dato',
                'Stato',
                'Compagnia'
            ], ';');

            // Streaming con chunk per gestire grandi dataset
            $query->limit($exportLimit)->chunk(500, function ($logs) use ($file) {
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->created_at->format('d/m/Y H:i:s'),
                        $log->user_name ?? '-',
                        $log->action_label,
                        $log->description,
                        $log->entity_label,
                        $log->entity_name ?? '-',
                        $this->translateStatus($log->status),
                        $log->compagnia?->nome ?? '-'
                    ], ';');
                }
            });

            fclose($file);
        };

        // Logga l'esportazione
        \App\Services\AuditService::logExport('audit_logs', min($totalCount, $exportLimit));

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Traduce lo stato in italiano per l'export.
     */
    protected function translateStatus(string $status): string
    {
        return match($status) {
            'success' => 'Completato',
            'failed' => 'Fallito',
            'warning' => 'Attenzione',
            default => $status
        };
    }
    
}
