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
     * 
     * Per utenti non-admin: mostra solo i log delle unità accessibili.
     * Per admin globali: mostra tutti i log.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Filtro per mese/anno (come CPT)
        $mese = $request->input('mese', now()->month);
        $anno = $request->input('anno', now()->year);

        // Query base ottimizzata con select specifico
        $query = AuditLog::query()
            ->select([
                'id', 'user_id', 'user_name', 'action', 'description',
                'entity_type', 'entity_id', 'entity_name', 
                'old_values', 'new_values',
                'compagnia_id', 'active_unit_id', 'affected_unit_id',
                'status', 'page_context', 'created_at'
            ])
            ->with([
                'user:id,name,username',
                'compagnia:id,nome'
            ])
            // Filtra per mese/anno selezionato
            ->whereMonth('created_at', $mese)
            ->whereYear('created_at', $anno)
            ->orderBy('created_at', 'desc');

        // =====================================================================
        // FILTRO PER UNITÀ ACCESSIBILI (pagina globale - mostra tutto l'accessibile)
        // Admin globali vedono tutto, altri utenti vedono solo log delle loro unità
        // =====================================================================
        if (!$user->hasRole('admin')) {
            $accessibleUnitIds = $user->getVisibleUnitIds();
            
            if (!empty($accessibleUnitIds)) {
                $query->where(function ($q) use ($accessibleUnitIds) {
                    $q->whereIn('active_unit_id', $accessibleUnitIds)
                      ->orWhereIn('affected_unit_id', $accessibleUnitIds)
                      ->orWhereNull('active_unit_id'); // Log senza contesto unità (login, ecc.)
                });
            } else {
                // Nessuna unità accessibile - mostra solo log dell'utente stesso
                $query->where('user_id', $user->id);
            }
        }

        // =====================================================================
        // FILTRI AGGIUNTIVI (usando scope del model per efficienza)
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

        // Carica tutti i log del mese (senza paginazione)
        $logs = $query->get();

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
        $user = Auth::user();
        
        // Verifica accesso: admin vede tutto, altri solo log delle loro unità
        if (!$user->hasRole('admin')) {
            $accessibleUnitIds = $user->getVisibleUnitIds();
            $canAccess = in_array($auditLog->active_unit_id, $accessibleUnitIds) 
                      || in_array($auditLog->affected_unit_id, $accessibleUnitIds)
                      || $auditLog->active_unit_id === null
                      || $auditLog->user_id === $user->id;
            
            if (!$canAccess) {
                abort(403, 'Non hai i permessi per visualizzare questo log.');
            }
        }

        $auditLog->load(['user:id,name,username', 'compagnia:id,nome']);

        return view('admin.audit-logs.show', compact('auditLog'));
    }

    /**
     * Mostra i log di un utente specifico.
     * 
     * Per utenti non-admin: mostra solo i log delle unità accessibili.
     */
    public function userLogs(User $targetUser)
    {
        $currentUser = Auth::user();
        $perPage = config('audit.per_page', 50);
        
        $query = AuditLog::select([
                'id', 'action', 'description', 'entity_type', 
                'entity_name', 'active_unit_id', 'affected_unit_id',
                'status', 'created_at'
            ])
            ->byUser($targetUser->id)
            ->orderBy('created_at', 'desc');

        // Filtro per unità accessibili (se non admin)
        if (!$currentUser->hasRole('admin')) {
            $accessibleUnitIds = $currentUser->getVisibleUnitIds();
            
            if (!empty($accessibleUnitIds)) {
                $query->where(function ($q) use ($accessibleUnitIds) {
                    $q->whereIn('active_unit_id', $accessibleUnitIds)
                      ->orWhereIn('affected_unit_id', $accessibleUnitIds)
                      ->orWhereNull('active_unit_id');
                });
            } else {
                // Nessuna unità accessibile - mostra solo i propri log
                $query->where('user_id', $currentUser->id);
            }
        }

        $logs = $query->paginate($perPage);

        return view('admin.audit-logs.user', compact('logs', 'targetUser'))->with('user', $targetUser);
    }

    /**
     * Mostra solo i log di accesso (login/logout).
     * 
     * Per utenti non-admin: mostra solo i log di accesso delle unità accessibili.
     */
    public function accessLogs(Request $request)
    {
        $user = Auth::user();
        $perPage = config('audit.per_page', 50);

        $query = AuditLog::select([
                'id', 'user_id', 'user_name', 'action', 
                'description', 'active_unit_id', 'status', 'created_at'
            ])
            ->accessLogs()
            ->with('user:id,name,username')
            ->orderBy('created_at', 'desc');

        // Filtro per unità accessibili (se non admin)
        // Per i log di accesso, filtriamo in base all'unità dell'utente loggato
        if (!$user->hasRole('admin')) {
            $accessibleUnitIds = $user->getVisibleUnitIds();
            
            if (!empty($accessibleUnitIds)) {
                // Mostra login/logout di utenti delle unità accessibili
                $query->where(function ($q) use ($accessibleUnitIds) {
                    $q->whereIn('active_unit_id', $accessibleUnitIds)
                      ->orWhereNull('active_unit_id'); // Login senza contesto unità
                });
            } else {
                // Nessuna unità accessibile - mostra solo i propri log
                $query->where('user_id', $user->id);
            }
        }

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
     * Esporta i log in Excel con formattazione professionale.
     * Filtra per mese/anno selezionato e per unità accessibili.
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        // Filtro per mese/anno
        $mese = $request->input('mese', now()->month);
        $anno = $request->input('anno', now()->year);
        
        $mesiItaliani = [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
        ];

        // Query con filtro mese/anno
        $query = AuditLog::select([
                'id', 'user_name', 'action', 'description',
                'entity_type', 'entity_name', 'status', 'page_context',
                'active_unit_id', 'affected_unit_id', 'compagnia_id', 'created_at'
            ])
            ->with('compagnia:id,nome')
            ->whereMonth('created_at', $mese)
            ->whereYear('created_at', $anno)
            ->orderBy('created_at', 'desc');

        // Filtro per unità accessibili (se non admin)
        if (!$user->hasRole('admin')) {
            $accessibleUnitIds = $user->getVisibleUnitIds();
            
            if (!empty($accessibleUnitIds)) {
                $query->where(function ($q) use ($accessibleUnitIds) {
                    $q->whereIn('active_unit_id', $accessibleUnitIds)
                      ->orWhereIn('affected_unit_id', $accessibleUnitIds)
                      ->orWhereNull('active_unit_id');
                });
            } else {
                $query->where('user_id', $user->id);
            }
        }

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

        $logs = $query->get();
        $totalCount = $logs->count();
        
        // Genera Excel con PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registro Attività');
        
        // Stili
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A2342']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];
        
        $successStyle = ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']]];
        $failedStyle = ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']]];
        
        // Intestazioni
        $headers = ['Data/Ora', 'Utente', 'Azione', 'Pagina', 'Descrizione', 'Tipo Dato', 'Stato'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);
        
        // Dati
        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValue('A' . $row, $log->created_at->format('d/m/Y H:i:s'));
            $sheet->setCellValue('B' . $row, $log->user_name ?? '-');
            $sheet->setCellValue('C' . $row, $log->action_label);
            $sheet->setCellValue('D' . $row, $log->page_context ?? '-');
            $sheet->setCellValue('E' . $row, $log->description);
            $sheet->setCellValue('F' . $row, $log->entity_label);
            $sheet->setCellValue('G' . $row, $this->translateStatus($log->status));
            
            // Colora la riga in base allo stato
            if ($log->status === 'success') {
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($successStyle);
            } elseif ($log->status === 'failed') {
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($failedStyle);
            }
            
            $row++;
        }
        
        // Auto-size colonne
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Bordi per tutte le celle con dati
        if ($row > 2) {
            $sheet->getStyle('A1:G' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        
        // Nome file con mese/anno
        $filename = 'registro_attivita_' . $mesiItaliani[$mese] . '_' . $anno . '.xlsx';
        
        // Logga l'esportazione
        \App\Services\AuditService::logExport('audit_logs', $totalCount);

        // Output
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
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
