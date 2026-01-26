<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;

/**
 * ExcelStyleService - Single Source of Truth per stili Excel SUGECO
 * 
 * Questo servizio gestisce tutti gli stili per l'export Excel,
 * garantendo coerenza con l'estetica delle pagine web.
 * 
 * Colori principali:
 * - Navy: #0A2342 (header tabelle)
 * - Navy Light: #0F3A6D
 * - Gold: #BF9D5E
 * - Success/Valido: #28a745 (gradient #d4edda -> #c3e6cb)
 * - Warning/In Scadenza: #ffc107 (gradient #fff3cd -> #ffeeba)
 * - Error/Scaduto: #dc3545 (gradient #f8d7da -> #f5c6cb)
 * - Mancante: #6c757d (gradient #f8f9fa -> #e9ecef)
 * - Prenotato: #007bff (gradient #cce5ff -> #b8daff)
 */
class ExcelStyleService
{
    // Colori principali SUGECO (senza #)
    public const NAVY = '0A2342';
    public const NAVY_LIGHT = '0F3A6D';
    public const GOLD = 'BF9D5E';
    public const GOLD_LIGHT = 'D7B677';
    
    // Colori scadenze
    public const VALIDO_BG = 'd4edda';
    public const VALIDO_BORDER = '28a745';
    public const VALIDO_TEXT = '155724';
    
    public const IN_SCADENZA_BG = 'fff3cd';
    public const IN_SCADENZA_BORDER = 'ffc107';
    public const IN_SCADENZA_TEXT = '856404';
    
    public const SCADUTO_BG = 'f8d7da';
    public const SCADUTO_BORDER = 'dc3545';
    public const SCADUTO_TEXT = '721c24';
    
    public const MANCANTE_BG = 'f8f9fa';
    public const MANCANTE_BORDER = 'adb5bd';
    public const MANCANTE_TEXT = '6c757d';
    
    public const PRENOTATO_BG = 'cce5ff';
    public const PRENOTATO_BORDER = '007bff';
    public const PRENOTATO_TEXT = '004085';
    
    // Colori presenti/assenti
    public const PRESENTE_BG = 'd4edda';
    public const PRESENTE_HEADER = '198754';
    public const PRESENTE_TEXT = '155724';
    
    public const ASSENTE_BG = 'f8d7da';
    public const ASSENTE_HEADER = 'dc3545';
    public const ASSENTE_TEXT = '721c24';
    
    // Altri colori
    public const WHITE = 'FFFFFF';
    public const BLACK = '000000';
    public const LIGHT_GRAY = 'F8F9FA';
    public const BORDER_GRAY = 'E2E8F0';
    public const ROW_ALTERNATE = 'F7FAFC';
    
    /**
     * Applica lo stile header principale (titolo pagina)
     */
    public function applyTitleStyle(Worksheet $sheet, string $range, string $title): void
    {
        $sheet->setCellValue(explode(':', $range)[0], $title);
        $sheet->mergeCells($range);
        
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 18,
                'color' => ['rgb' => self::NAVY],
                'name' => 'Calibri'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6F2FF']
            ]
        ]);
        
        // Altezza riga titolo
        $startRow = (int) preg_replace('/[^0-9]/', '', explode(':', $range)[0]);
        $sheet->getRowDimension($startRow)->setRowHeight(40);
    }
    
    /**
     * Applica lo stile header tabella (colonne)
     */
    public function applyHeaderStyle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => self::WHITE],
                'name' => 'Calibri'
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::NAVY]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::NAVY_LIGHT]
                ]
            ]
        ]);
    }
    
    /**
     * Applica lo stile per le righe dati
     */
    public function applyDataStyle(Worksheet $sheet, string $range, bool $centerAlign = true): void
    {
        $style = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::BORDER_GRAY]
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        
        if ($centerAlign) {
            $style['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
        }
        
        $sheet->getStyle($range)->applyFromArray($style);
    }
    
    /**
     * Applica alternanza colore righe
     */
    public function applyAlternateRowColors(Worksheet $sheet, int $startRow, int $endRow, string $startCol, string $endCol): void
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("{$startCol}{$row}:{$endCol}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::ROW_ALTERNATE]
                    ]
                ]);
            }
        }
    }
    
    /**
     * Applica stile cella scadenza in base allo stato
     */
    public function applyScadenzaStyle(Worksheet $sheet, string $cell, string $stato): void
    {
        $colors = $this->getScadenzaColors($stato);
        
        $sheet->getStyle($cell)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $colors['bg']]
            ],
            'font' => [
                'color' => ['rgb' => $colors['text']],
                'bold' => $stato !== 'mancante'
            ],
            'borders' => [
                'left' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => $colors['border']]
                ],
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::BORDER_GRAY]
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }
    
    /**
     * Ottiene i colori per uno stato scadenza
     */
    public function getScadenzaColors(string $stato): array
    {
        return match ($stato) {
            'valido' => [
                'bg' => self::VALIDO_BG,
                'border' => self::VALIDO_BORDER,
                'text' => self::VALIDO_TEXT
            ],
            'in_scadenza' => [
                'bg' => self::IN_SCADENZA_BG,
                'border' => self::IN_SCADENZA_BORDER,
                'text' => self::IN_SCADENZA_TEXT
            ],
            'scaduto' => [
                'bg' => self::SCADUTO_BG,
                'border' => self::SCADUTO_BORDER,
                'text' => self::SCADUTO_TEXT
            ],
            'prenotato' => [
                'bg' => self::PRENOTATO_BG,
                'border' => self::PRENOTATO_BORDER,
                'text' => self::PRENOTATO_TEXT
            ],
            default => [ // mancante
                'bg' => self::MANCANTE_BG,
                'border' => self::MANCANTE_BORDER,
                'text' => self::MANCANTE_TEXT
            ]
        };
    }
    
    /**
     * Calcola lo stato di una scadenza
     */
    public function calcolaStatoScadenza($dataConseguimento, int $durataAnni = 1, string $unita = 'anni'): array
    {
        if (!$dataConseguimento) {
            return [
                'data_conseguimento' => null,
                'data_scadenza' => null,
                'stato' => 'mancante',
                'giorni_rimanenti' => null
            ];
        }
        
        $data = Carbon::parse($dataConseguimento);
        $oggi = Carbon::now();
        
        // Verifica se è prenotato (data nel futuro)
        if ($data->isFuture()) {
            $scadenza = $unita === 'mesi' 
                ? $data->copy()->addMonths($durataAnni) 
                : $data->copy()->addYears($durataAnni);
            
            return [
                'data_conseguimento' => $data,
                'data_scadenza' => $scadenza,
                'stato' => 'prenotato',
                'giorni_rimanenti' => $oggi->diffInDays($scadenza, false)
            ];
        }
        
        $scadenza = $unita === 'mesi' 
            ? $data->copy()->addMonths($durataAnni) 
            : $data->copy()->addYears($durataAnni);
        
        $giorniRimanenti = $oggi->diffInDays($scadenza, false);
        
        $stato = match (true) {
            $giorniRimanenti < 0 => 'scaduto',
            $giorniRimanenti <= 30 => 'in_scadenza',
            default => 'valido'
        };
        
        return [
            'data_conseguimento' => $data,
            'data_scadenza' => $scadenza,
            'stato' => $stato,
            'giorni_rimanenti' => abs($giorniRimanenti)
        ];
    }
    
    /**
     * Applica stile header sezione (es. "PRESENTI", "ASSENTI")
     */
    public function applySectionHeaderStyle(Worksheet $sheet, string $range, string $type = 'primary'): void
    {
        $bgColor = match ($type) {
            'success', 'presente' => self::PRESENTE_HEADER,
            'danger', 'assente' => self::ASSENTE_HEADER,
            default => self::NAVY
        };
        
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => self::WHITE]
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $bgColor]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }
    
    /**
     * Applica stile per riepilogo/totali
     */
    public function applyTotalsStyle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => self::NAVY]
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F1F5F9']
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => self::NAVY]
                ],
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::BORDER_GRAY]
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }
    
    /**
     * Applica stile per badge/etichette
     */
    public function applyBadgeStyle(Worksheet $sheet, string $cell, string $type = 'primary'): void
    {
        $colors = match ($type) {
            'success' => ['bg' => self::PRESENTE_HEADER, 'text' => self::WHITE],
            'danger' => ['bg' => self::ASSENTE_HEADER, 'text' => self::WHITE],
            'warning' => ['bg' => self::IN_SCADENZA_BORDER, 'text' => self::BLACK],
            'info' => ['bg' => self::PRENOTATO_BORDER, 'text' => self::WHITE],
            default => ['bg' => self::NAVY, 'text' => self::WHITE]
        };
        
        $sheet->getStyle($cell)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => $colors['text']]
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $colors['bg']]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }
    
    /**
     * Imposta larghezze colonne standard per tabelle militari
     */
    public function setStandardColumnWidths(Worksheet $sheet, array $columns): void
    {
        $standardWidths = [
            'numero' => 6,
            'compagnia' => 15,
            'grado' => 14,
            'cognome' => 20,
            'nome' => 18,
            'plotone' => 20,
            'ufficio' => 30,
            'incarico' => 30,
            'patenti' => 16,
            'nos' => 10,
            'anzianita' => 14,
            'data_nascita' => 14,
            'email' => 35,
            'telefono' => 16,
            'data_cons' => 14,
            'data_scad' => 14,
            'motivazione' => 35
        ];
        
        $col = 'A';
        foreach ($columns as $column) {
            $width = $standardWidths[$column] ?? 15;
            $sheet->getColumnDimension($col)->setWidth($width);
            $col++;
        }
    }
    
    /**
     * Aggiunge una riga di scadenza con stile appropriato
     */
    public function addScadenzaRow(
        Worksheet $sheet, 
        int $row, 
        string $colCons, 
        $dataConseguimento, 
        int $durata, 
        string $unita = 'anni'
    ): void {
        $colScad = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($colCons) + 1);
        
        $scadenza = $this->calcolaStatoScadenza($dataConseguimento, $durata, $unita);
        
        if (!$dataConseguimento) {
            $sheet->setCellValue($colCons . $row, '-');
            $sheet->setCellValue($colScad . $row, '-');
            $this->applyScadenzaStyle($sheet, $colCons . $row, 'mancante');
            $this->applyScadenzaStyle($sheet, $colScad . $row, 'mancante');
            return;
        }
        
        $data = Carbon::parse($dataConseguimento);
        $dataScadenza = $scadenza['data_scadenza'];
        
        $sheet->setCellValue($colCons . $row, $data->format('d/m/Y'));
        $sheet->setCellValue($colScad . $row, $dataScadenza->format('d/m/Y'));
        
        // Stile per data conseguimento (neutro)
        $this->applyDataStyle($sheet, $colCons . $row);
        
        // Stile per data scadenza (colorato in base allo stato)
        $this->applyScadenzaStyle($sheet, $colScad . $row, $scadenza['stato']);
    }
    
    /**
     * Crea spreadsheet base con impostazioni standard
     */
    public function createSpreadsheet(string $title = 'SUGECO Export'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Impostazioni documento
        $spreadsheet->getProperties()
            ->setCreator('SUGECO')
            ->setTitle($title)
            ->setSubject($title)
            ->setCompany('SUGECO - Sistema Unico di Gestione e Controllo');
        
        // Font predefinito
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        
        return $spreadsheet;
    }
    
    /**
     * Aggiunge data e ora di generazione
     */
    public function addGenerationInfo(Worksheet $sheet, int $row, string $startCol = 'A'): void
    {
        $sheet->setCellValue($startCol . $row, 'Generato il: ' . Carbon::now()->format('d/m/Y H:i'));
        $sheet->getStyle($startCol . $row)->applyFromArray([
            'font' => [
                'italic' => true,
                'size' => 9,
                'color' => ['rgb' => self::MANCANTE_TEXT]
            ]
        ]);
    }
    
    /**
     * Applica stile per celle con testo lungo (wrap + allineamento sinistra)
     */
    public function applyLongTextStyle(Worksheet $sheet, string $cell): void
    {
        $sheet->getStyle($cell)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ]);
    }
    
    /**
     * Applica freeze pane per header sticky
     */
    public function freezeHeader(Worksheet $sheet, int $headerRow = 1): void
    {
        $sheet->freezePane('A' . ($headerRow + 1));
    }
    
    /**
     * Imposta area di stampa
     */
    public function setPrintArea(Worksheet $sheet, string $lastColumn, int $lastRow): void
    {
        $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$lastRow}");
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
    }
    
    /**
     * Aggiunge legenda scadenze
     */
    public function addLegenda(Worksheet $sheet, int $row, string $startCol = 'A'): void
    {
        $legenda = [
            ['stato' => 'valido', 'label' => 'Valido'],
            ['stato' => 'in_scadenza', 'label' => 'In Scadenza (≤30gg)'],
            ['stato' => 'scaduto', 'label' => 'Scaduto'],
            ['stato' => 'prenotato', 'label' => 'Prenotato'],
            ['stato' => 'mancante', 'label' => 'Non presente']
        ];
        
        $sheet->setCellValue($startCol . $row, 'LEGENDA:');
        $sheet->getStyle($startCol . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10]
        ]);
        
        $col = $startCol;
        $col++; // Avanza di una colonna
        
        foreach ($legenda as $item) {
            $colLetter = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($col));
            $sheet->setCellValue($colLetter . $row, $item['label']);
            $this->applyScadenzaStyle($sheet, $colLetter . $row, $item['stato']);
            $col = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($colLetter) + 1);
        }
    }
}
