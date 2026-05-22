<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'store_categories';

    public function sections()
    {
        return $this->hasMany(Section::class, 'store_category_id');
    }
}
