<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Municipality;

class MunicipalityController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $zipCode
     * @return \Illuminate\Http\Response
     */
    public function zipCode(Request $request, $zipCode)
    {
        $detail = (object) Municipality::whereZipCode($zipCode)->firstOrFail()->detail;

        return [
            'zip_code' => $detail->zip_code,
            'locality' => $detail->locality,
            'federal_entity' => (object) [...$detail->federal_entity, ...['code' => null]],
            'settlements' => $detail->settlements,
            'municipality' => $detail->municipality
        ];
    }
}
