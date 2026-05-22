<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    public function options()
    {
        return $this->hasMany(ProductOption::class, 'product_id');
    }

    public function addons()
    {
        return $this->hasMany(ProductAddon::class, 'product_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function nestedSection()
    {
        return $this->belongsTo(NestedSection::class, 'store_nested_section_id');
    }
}
