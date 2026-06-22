<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class UpdateTypeCpuRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_cpu';
    }
}
