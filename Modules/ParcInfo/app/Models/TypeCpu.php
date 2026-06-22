<?php

namespace Modules\ParcInfo\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\ParcInfo\Models\Traits\MapsToDictionnaireValeur;

class TypeCpu extends Model
{
    use MapsToDictionnaireValeur;

    protected $fillable = ['description'];

    protected static function getDictionnaireCode(): string
    {
        return 'type_cpu';
    }
}
