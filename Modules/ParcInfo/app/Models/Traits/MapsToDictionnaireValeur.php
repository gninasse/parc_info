<?php

namespace Modules\ParcInfo\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\ParcInfo\Models\Builders\DictionnaireValeurBuilder;

trait MapsToDictionnaireValeur
{
    public static function bootMapsToDictionnaireValeur()
    {
        $code = static::getDictionnaireCode();

        static::addGlobalScope($code, function (Builder $builder) use ($code) {
            $builder->where('dictionnaire_id', function ($query) use ($code) {
                $query->select('id')->from('parc_info_dictionnaires')->where('code', $code);
            });
        });

        static::creating(function ($model) use ($code) {
            if (! $model->dictionnaire_id) {
                $model->dictionnaire_id = \DB::table('parc_info_dictionnaires')->where('code', $code)->value('id');
            }
        });
    }

    public function initializeMapsToDictionnaireValeur()
    {
        $this->table = 'parc_info_dictionnaire_valeurs';
        $this->fillable = array_merge($this->fillable ?? [], ['valeur', 'dictionnaire_id', 'description', 'libelle']);
        $this->appends = array_merge($this->appends ?? [], ['libelle']);
    }

    abstract protected static function getDictionnaireCode(): string;

    public function getLibelleAttribute()
    {
        return $this->valeur;
    }

    public function setLibelleAttribute($value)
    {
        $this->valeur = $value;
    }

    public function newEloquentBuilder($query)
    {
        return new DictionnaireValeurBuilder($query);
    }
}
