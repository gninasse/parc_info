<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class StoreTypeRamRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_ram';
    }
}
