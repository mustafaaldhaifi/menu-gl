<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $table = 'store_sections';

    public function nestedSections()
    {
        return $this->hasMany(NestedSection::class, 'store_section_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'store_category_id');
    }
}
