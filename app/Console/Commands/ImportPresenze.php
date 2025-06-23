<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Presenza;
use App\Models\Militare;
use Carbon\Carbon;

class ImportPresenze extends Command
{
    protected $signature = 'import:presenze {file}';
    protected $description = 'Importa le presenze da un file Excel';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Il file non esiste: $filePath");
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $today = Carbon::today()->format('Y-m-d');

        // Conta quanti militari vengono effettivamente trovati e importati
        $foundCount = 0;

        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            // Salta la prima riga (intestazione)
            if ($rowIndex === 1) {
                continue;
            }

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            // Se mancano colonne, salta la riga
            if (count($data) < 4) {
                continue;
            }

            // Lettura "grezza" dal file
            $gradoRaw   = trim($data[0]);
            $cognomeRaw = trim($data[1]);
            $nomeRaw    = trim($data[2]);

            // Normalizziamo i campi (rimuovendo spazi e convertendo in maiuscolo)
            $gradoNormalized   = strtoupper(str_replace(' ', '', $gradoRaw));
            $cognomeNormalized = strtoupper(str_replace(' ', '', $cognomeRaw));
            $nomeNormalized    = strtoupper(str_replace(' ', '', $nomeRaw));

            // Controllo robusto su “X” (assente)
            $valorePresenza = trim(strtoupper($data[3]));
            $isPresente     = ($valorePresenza === 'X') ? false : true;

            // Log di debug per ogni riga
            $this->info("Riga $rowIndex => [Grado: $gradoRaw, Cognome: $cognomeRaw, Nome: $nomeRaw, ValorePresenza: '$valorePresenza'] -> is_present=".($isPresente ? 'true' : 'false'));

            // Trova il record del grado nella tabella 'gradi', ignorando spazi e maiuscole
            $gradoRecord = \DB::table('gradi')
                ->whereRaw("UPPER(REPLACE(nome, ' ', '')) = ?", [$gradoNormalized])
                ->first();

            if (!$gradoRecord) {
                $this->error("Grado non trovato: '$gradoRaw' per $cognomeRaw $nomeRaw. Riga ignorata.");
                continue;
            }

            // Trova il militare nella tabella 'militari', ignorando spazi/maiuscole in cognome e nome
            $militare = Militare::where('grado_id', $gradoRecord->id)
                ->whereRaw("UPPER(REPLACE(cognome, ' ', '')) = ?", [$cognomeNormalized])
                ->whereRaw("UPPER(REPLACE(nome, ' ', '')) = ?", [$nomeNormalized])
                ->first();

            if (!$militare) {
                $this->error("Militare non trovato: $gradoRaw $cognomeRaw $nomeRaw. Riga ignorata.");
                continue;
            }

            // Salviamo il valore "Presente" o "Assente" in 'stato'
            Presenza::updateOrCreate(
                [
                    'militare_id' => $militare->id,
                    'data'        => $today
                ],
                [
                    'stato' => $isPresente ? 'Presente' : 'Assente'
                ]
            );

            $foundCount++;
        }

        $this->info("Importazione completata con successo! Militari trovati e analizzati: $foundCount");
    }
}
