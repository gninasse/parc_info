<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class UpdateTypeRamRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_ram';
    }
}
