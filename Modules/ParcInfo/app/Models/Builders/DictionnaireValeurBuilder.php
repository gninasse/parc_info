<?php

namespace Modules\ParcInfo\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

class DictionnaireValeurBuilder extends Builder
{
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column === 'libelle') {
            $column = 'valeur';
        }

        return parent::where($column, $operator, $value, $boolean);
    }
}
