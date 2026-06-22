<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class StoreTypeDisqueRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_disque';
    }
}
