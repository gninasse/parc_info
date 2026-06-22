<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class StoreTypeImprimanteRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_imprimante';
    }
}
