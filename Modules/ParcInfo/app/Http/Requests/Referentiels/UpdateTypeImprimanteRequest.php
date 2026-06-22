<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class UpdateTypeImprimanteRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_imprimante';
    }
}
