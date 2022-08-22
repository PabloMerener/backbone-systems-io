<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithConditionalSheets;

class MultipleSheetImport implements WithMultipleSheets
{
    use WithConditionalSheets;

    /**
     * sheets
     *
     * @var array
     */
    private $sheets = [];

    public function __construct(array $sheets)
    {
        foreach ($sheets as $sheet) {
            $this->sheets[$sheet] = new CollectionImport();
        }
    }

    /**
     * sheets
     *
     * @return array
     */
    public function sheets(): array
    {
        return $this->sheets;
    }

    /**
     * conditionalSheets
     *
     * @return array
     */
    public function conditionalSheets(): array
    {
        return $this->sheets();
    }

    /**
     * Get rows (get data)
     *
     * @param  string $sheet
     * @return Illuminate\Support\Collection
     */
    public function getRows(string $sheet)
    {
        return $this->sheets[$sheet]->getRows();
    }
}
