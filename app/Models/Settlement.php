<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    use HasFactory;

    public function type()
    {
        return $this->belongsTo(SettlementType::class, 'type_id');
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }
}
