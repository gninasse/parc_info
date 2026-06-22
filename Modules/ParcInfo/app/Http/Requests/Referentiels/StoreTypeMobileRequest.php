<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class StoreTypeMobileRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_mobile';
    }
}
