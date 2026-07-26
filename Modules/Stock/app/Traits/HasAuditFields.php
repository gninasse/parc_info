<?php

namespace Modules\Stock\Traits;

use Illuminate\Support\Facades\Auth;

trait HasAuditFields
{
    public static function bootHasAuditFields(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                if (! $model->created_by) {
                    $model->created_by = Auth::id();
                }
                if (! $model->updated_by) {
                    $model->updated_by = Auth::id();
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                if (! $model->updated_by) {
                    $model->updated_by = Auth::id();
                }
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
