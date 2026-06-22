<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class StoreTypeCpuRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_cpu';
    }
}
