<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Imports\ExcelUtils;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ZipCode;

class ZipCodeSeeder extends Seeder
{
    const DATA_SOURCE_PATH = 'database' . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . 'CPdescarga.xls';
    // const DATA_SOURCE_PATH = 'database' . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . 'CPdescarga_2_estados.xls';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get spreadsheet
        $path = base_path() . DIRECTORY_SEPARATOR . self::DATA_SOURCE_PATH;
        Excel::import($spreadsheet = new ExcelUtils, $path);

        // Persist sheets to database
        foreach ($spreadsheet->getSheetNames() as $key => $sheetName) {
            $this->command->line($sheetName);
            if ($key > 0) { // skip first sheet (Nota)
                $this->sheetProcess($spreadsheet->sheetData[$sheetName]);
            }
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
     * @param  array $rows
     * @return void
     */
    public function sheetProcess(array $rows)
    {
        foreach ($rows as $key => $row) {
            $line = (object) $row;
            $this->command->line($line->d_codigo);

            if ($key === 0 || $ZipCode->zip_code !== $line->d_codigo) {
                // Zip code (column d_codigo) break control
                if ($key > 0) {
                    ZipCode::create([
                        'code' => $ZipCode->zip_code,
                        'detail' => $ZipCode
                    ]);
                }
                $ZipCode = (object) [
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
                // Each row has a unique settlement, the others columns correspond to the postal code
                $ZipCode->settlements[] = (object) [
                    'key' => (int) $line->id_asenta_cpcons,
                    'name' => $this->removeAccents($line->d_asenta),
                    'zone_type' => $this->removeAccents($line->d_zona),
                    'settlement_type' => (object) ['name' => $line->d_tipo_asenta]
                ];
            }
        }

        if ($ZipCode ?? false) { // Save last sheet zip code
            ZipCode::create([
                'code' => $ZipCode->zip_code,
                'detail' => $ZipCode,
            ]);
        }
    }
}
