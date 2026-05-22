<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NestedSection extends Model
{
    protected $table = 'store_nested_sections';

    public function products()
    {
        return $this->hasMany(Product::class, 'store_nested_section_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'store_section_id');
    }
}
