<?php

namespace App\Http\Controllers;

use App\Models\ZipCode;
use Illuminate\Http\Request;

class ZipCodeController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $zip_code
     * @return \Illuminate\Http\Response
     */
    public function zip_code(Request $request, $zip_code)
    {
        $detail = (object) ZipCode::whereCode($zip_code)->firstOrFail()->detail;

        return [
            'zip_code' => $detail->zip_code,
            'locality' => $detail->locality,
            'federal_entity' => (object) [...$detail->federal_entity, ...['code' => null]],
            'settlements' => $detail->settlements,
            'municipality' => $detail->municipality
        ];
    }
}
