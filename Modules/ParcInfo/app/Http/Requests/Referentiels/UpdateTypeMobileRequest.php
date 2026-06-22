<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

class UpdateTypeMobileRequest extends BaseDictionnaireRequest
{
    protected function getDictionnaireCode(): string
    {
        return 'type_mobile';
    }
}
