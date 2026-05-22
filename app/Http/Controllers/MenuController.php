<?php

namespace App\Http\Controllers;

use App\Models\Product;

class MenuController extends Controller
{
    public function index()
    {
        $branchId = 1;

        $products = Product::where('store_branch_id', $branchId)
            ->with([
                'options' => fn($q) => $q->where('store_branch_id', $branchId),
                'addons' => fn($q) => $q->where('store_branch_id', $branchId),
                'images' => fn($q) => $q->where('store_branch_id', $branchId),
                'nestedSection' => fn($q) => $q->where('store_branch_id', $branchId),
                'nestedSection.section' => fn($q) => $q->where('store_branch_id', $branchId),
                'nestedSection.section.category' => fn($q) => $q->where('store_branch_id', $branchId),
            ])
            ->get();

        $categories = \App\Models\Category::where('store_branch_id', $branchId)->get();
        $storeViews = \App\Models\StoreProductView::where('store_branch_id', $branchId)->get();

     
        $formattedProducts = $products->map(function ($product) {
            $nestedSection = $product->nestedSection;
            $section = $nestedSection?->section;
            $category = $section?->category;
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'image' => $product->cover ? 'https://apps77.s3.ap-southeast-1.amazonaws.com/covers/products/' . $product->cover : asset('images/default-food.png'),
                'category_id' => $category?->id,
                'section_id' => $section?->id,
                'section_name' => $section?->name,
                'nested_section_id' => $nestedSection?->id,
                'nested_section_name' => $nestedSection?->name,
                'view_id' => $product->store_product_view_id,
                'show_out' => (int)$product->show_out,
                'tags' => [], 
                'images' => $product->images->map(function($img) {
                    return 'https://apps77.s3.ap-southeast-1.amazonaws.com/products/' . $img->image;
                }),
                'options' => $product->options->map(function ($opt) {
                    return [
                        'id' => $opt->id,
                        'name' => $opt->name,
                        'price' => (float)$opt->price,
                        'image' => $opt->cover ? 'https://apps77.s3.ap-southeast-1.amazonaws.com/covers/options/' . $opt->cover : 'https://images.unsplash.com/photo-1495147466023-ac3c75324fad?auto=format&fit=crop&w=300&q=80',
                        'description' => $opt->description,
                        'show_out' => (int)$opt->show_out
                    ];
                }),
                'addons' => $product->addons->map(function ($addon) {
                    return [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'price' => (float)$addon->price
                    ];
                })
            ];
        });

        //    Log::error($products);

        //    Log::error($formattedProducts);
        $formattedCategories = $categories->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name
            ];
        });

        $formattedViews = $storeViews->filter(function($view) {
            return !empty($view->name) && $view->name !== '.';
        })->map(function ($view) {
            return [
                'id' => $view->id,
                'name' => $view->name
            ];
        })->values();

        // Add 'All' category at the beginning
        $formattedCategories->prepend(['id' => 'all', 'name' => '🍽️ الكل']);

        return response()->json([
            'products' => $formattedProducts,
            'categories' => $formattedCategories,
            'views' => $formattedViews
        ]);
    }
}
