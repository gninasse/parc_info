<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class StoreTypeOsRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_os';
    }
}
