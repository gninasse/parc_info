<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class UpdateTypeDisqueRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_disque';
    }
}
