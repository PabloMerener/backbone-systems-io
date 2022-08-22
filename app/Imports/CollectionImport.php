<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CollectionImport implements ToCollection
{
    /**
     * rows
     *
     * @var Collection
     */
    private $rows;

    /**
     * @param Illuminate\Support\Collection $row
     *
     * @return null
     */
    public function collection(Collection $row)
    {
        $this->rows = $row;
    }

    /**
     * getRows
     *
     * @return Illuminate\Support\Collection
     */
    public function getRows()
    {
        return $this->rows;
    }
}
