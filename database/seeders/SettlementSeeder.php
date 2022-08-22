<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Imports\ExcelUtils;
use App\Imports\MultipleSheetImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\State;
use App\Models\City;
use App\Models\Municipality;
use App\Models\SettlementType;
use App\Models\Settlement;

class SettlementSeeder extends Seeder
{
    const DATA_SOURCE_PATH = 'database' . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . 'CPdescarga.xls';

    protected array $states = [];
    protected array $cities = [];
    protected array $municipalities = [];
    protected array $settlement_types = [];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->spreadsheetToDB();
        $this->setMunicipalityJson();
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
     * sheetProcess
     *
     * @param  Collection $rows
     * @return void
     */
    public function sheetProcess(Collection $rows)
    {
        foreach ($rows as $key => $row) {
            if (!$key) {
                $stateObj = new State;
                $cityObj = new City;
                $municipalityObj = new Municipality;
                $settlementTypeObj = new SettlementType;

                // get header
                $header = $row;
                continue;
            }

            $line = (object) array_combine(array_values($header->toArray()), array_values($row->toArray()));
            $this->command->line($line->d_codigo);

            // State
            $stateId = $this->getId($stateObj, $line->c_estado, ['key' => $line->c_estado, 'name' => $line->d_estado]);

            // City
            if ($line->d_ciudad) {
                $cityId = $this->getId(
                    $cityObj,
                    trim($stateId . $line->c_cve_ciudad),
                    ['state_id' => $stateId, 'key' => $line->c_cve_ciudad, 'name' => $line->d_ciudad]
                );
            } else {
                $cityId = null;
            }

            // Municipality
            $municipalityId = $this->getId(
                $municipalityObj,
                $line->d_codigo,
                [
                    'state_id' => $stateId,
                    'city_id' => $cityId,
                    'key' => $line->c_mnpio,
                    'name' => $line->D_mnpio,
                    'zip_code' => $line->d_codigo,
                ]
            );

            // SettlementType
            $settlementTypeId = $this->getId(
                $settlementTypeObj,
                $line->c_tipo_asenta,
                ['key' => $line->c_tipo_asenta, 'name' => $line->d_tipo_asenta]
            );

            // Settlement
            Settlement::create([
                'municipality_id' => $municipalityId,
                'type_id' => $settlementTypeId,
                'key' => $line->id_asenta_cpcons,
                'name' => $line->d_asenta,
                'zone_type' => $line->d_zona,
            ]);
        }
    }

    /**
     * getId
     *
     * @param  Illuminate\Database\Eloquent\Model $model
     * @param  string $key
     * @param  array $data
     * @return int id
     */
    public function getId(Model $model, string $key, array $data)
    {
        $table = $model->getTable();
        $list = &$this->{$table};
        return $list[$key] ?? $list[$key] = $model::create($data)->id;
    }

    /**
     * setMunicipalityJson (municipalities.detail)
     *
     * @return void
     */
    public function setMunicipalityJson()
    {
        $municipalities = Municipality::with('state', 'city', 'settlements.type')->get();

        foreach ($municipalities as $key => $municipality) {
            $this->command->line($municipality->zip_code);
            $municipality->fill(['detail' => [
                'zip_code' => $municipality->zip_code,
                'locality' => strtoupper($municipality->city->name ?? ''),
                'federal_entity' => [
                    'key' => $municipality->state->key,
                    'name' => strtoupper($municipality->state->name),
                ],
                'settlements' => $municipality->settlements->map(
                    fn ($e) => [
                        'key' => $e->key,
                        'name' => strtoupper($e->name),
                        'zone_type' => $e->zone_type,
                        'settlement_type' => [
                            'name' => $e->type->name
                        ]
                    ]
                ),
                'municipality' => [
                    'key' => $municipality->key,
                    'name' => strtoupper($municipality->name)
                ]
            ]])->save();
        }
    }
}
