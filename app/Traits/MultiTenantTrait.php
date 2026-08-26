<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;


trait MultiTenantTrait
{
    protected static function bootMultiTenantTrait()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->company_id = Auth::user()->company_id;
            }
        });

        static::addGlobalScope('company', function (Builder $builder) {
            if (Auth::check() && Auth::user()->company_id) {
                $builder->where('company_id', Auth::user()->company_id);
            }
        });
    }

    public function scopeWithoutCompanyScope($query)
    {
        return $query->withoutGlobalScope('company');
    }
}