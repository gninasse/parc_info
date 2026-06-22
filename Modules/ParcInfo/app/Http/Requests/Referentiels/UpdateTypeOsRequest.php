<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class UpdateTypeOsRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_os';
    }
}
