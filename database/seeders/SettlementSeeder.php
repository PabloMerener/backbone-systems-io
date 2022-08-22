<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Imports\ExcelUtils;
use App\Imports\MultipleSheetImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\SettlementType;
use App\Models\Settlement;

class SettlementSeeder extends Seeder
{
    const DATA_SOURCE_PATH = 'database' . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . 'CPdescarga_small.xls';

    protected array $settlement_types = [];
    protected array $settlements = [];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settlementTypeObj = new SettlementType;
        $settlementObj = new Settlement;

        $sheetNames = $this->getSheetNames();
        $import = new MultipleSheetImport($sheetNames);

        foreach ($sheetNames as $key => $sheetName) {
            if (!$key) continue; // skip firs sheet (Nota)

            $this->command->line($sheetName);
            $import->onlySheets($sheetName);
            Excel::import($import, $this->getPath());
            $rows = $import->getRows($sheetName);
            foreach ($rows as $key => $row) {
                if (!$key) {
                    // get header
                    $header = $row;
                    continue;
                }
                $line = (object) [];
                foreach ($header as $key => $column) {
                    $line->{$column} = $row[$key];
                }

                // SettlementType
                $settlementTypeId = $this->getId(
                    $settlementTypeObj,
                    [
                        'key' => $line->c_tipo_asenta,
                        'name' => $line->d_tipo_asenta
                    ]
                );

                // Settlement
                $settlementId = $this->getId(
                    $settlementObj,
                    [
                        'type_id' => $settlementTypeId,
                        'key' => $line->id_asenta_cpcons,
                        'name' => $line->d_asenta,
                        'zip_code' => $line->d_codigo,
                        'zone_type' => $line->d_zona,
                    ]
                );
                $this->command->line($line->d_codigo);
            }
        }
    }

    public function getPath()
    {
        return base_path() . DIRECTORY_SEPARATOR . self::DATA_SOURCE_PATH;
    }

    public function getSheetNames()
    {
        Excel::import($importSheets = new ExcelUtils, $this->getPath());
        return $importSheets->getSheetNames();
    }

    /**
     * getId
     *
     * @param  Illuminate\Database\Eloquent\Model $obj
     * @param  array $data
     * @return int id
     */
    public function getId(Model $obj, array $data)
    {
        $table = $obj->getTable();
        $name = $data['name'];
        $list = &$this->{$table};
        return $list[$name] ?? $list[$name] = $obj::create($data)->id;
    }
}
