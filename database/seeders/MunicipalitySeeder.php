<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Imports\ExcelUtils;
use App\Imports\MultipleSheetImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Municipality;

class MunicipalitySeeder extends Seeder
{
    const DATA_SOURCE_PATH = 'database' . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . 'CPdescarga.xls';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->spreadsheetToDB();
    }

    /**
     * spreadsheetToDB
     *
     * @return void
     */
    public function spreadsheetToDB()
    {
        $path = base_path() . DIRECTORY_SEPARATOR . self::DATA_SOURCE_PATH;
        Excel::import($importSheets = new ExcelUtils, $path);
        $sheetNames = $importSheets->getSheetNames();
        $import = new MultipleSheetImport($sheetNames);

        foreach ($sheetNames as $key => $sheetName) {
            if (!$key) continue; // skip firs sheet (Nota)

            $this->command->line($sheetName);
            $import->onlySheets($sheetName);
            Excel::import($import, $path);
            $rows = $import->getRows($sheetName);
            $this->sheetProcess($rows);
        }
    }

    /**
     * removeAccents (& to uppercase)
     *
     * @param  string $string
     * @return string
     */
    public function removeAccents(string|null $string)
    {
        try {
            $result = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        } catch (\Throwable $th) {
            $this->command->error($string);
            $result = $string;
        }

        return strtoupper($result);
    }

    /**
     * sheetProcess
     *
     * @param  Collection $rows
     * @return void
     */
    public function sheetProcess(Collection $rows)
    {
        foreach ($rows as $key => $row) {
            if ($key === 0) {
                $header = $row;
            } else {
                $line = (object) array_combine(array_values($header->toArray()), array_values($row->toArray()));
                $this->command->line($line->d_codigo);

                if ($key === 1 || $municipality->zip_code !== $line->d_codigo) {
                    if ($key > 1) {
                        Municipality::create([
                            'zip_code' => $municipality->zip_code,
                            'detail' => $municipality
                        ]);
                    }
                    $municipality = (object) [
                        'zip_code' => $line->d_codigo,
                        'locality' => $this->removeAccents($line->d_ciudad),
                        'federal_entity' => (object) [
                            'key' => (int) $line->c_estado,
                            'name' => $this->removeAccents($line->d_estado)
                        ],
                        'settlements' => [
                            (object) [
                                'key' => (int) $line->id_asenta_cpcons,
                                'name' => $this->removeAccents($line->d_asenta),
                                'zone_type' => $this->removeAccents($line->d_zona),
                                'settlement_type' => (object) ['name' => $line->d_tipo_asenta]
                            ]
                        ],
                        'municipality' => (object) [
                            'key' => (int) $line->c_mnpio,
                            'name' => $this->removeAccents($line->D_mnpio)
                        ],
                    ];
                } else {
                    $municipality->settlements[] = (object) [
                        'key' => (int) $line->id_asenta_cpcons,
                        'name' => $this->removeAccents($line->d_asenta),
                        'zone_type' => $this->removeAccents($line->d_zona),
                        'settlement_type' => (object) ['name' => $line->d_tipo_asenta]
                    ];
                }
            }
        }
        if ($municipality ?? false) {
            Municipality::create([
                'zip_code' => $municipality->zip_code,
                'detail' => json_encode($municipality),
            ]);
        }
    }
}
