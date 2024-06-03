<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Products;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $category = Category::all();
        if ($category) {

            return response()->json([
                'message' => 'Lists of all category',
                'category' => $category,

            ], 200);
        } else {

            return response([
                'message' => 'There is no product on the list',
            ], 204);
        }
    }


    public function store(Request $request)
    {
        $input =  $request->validate([
            'name' => 'required',
        ]);

        $category = Category::create($input);
        if ($category->save()) {
            return response()->json([
                'message' => 'Success!',
                'category' => $category
            ], 200);
        } else {

            return response([
                'message' => 'Category is failed to create!',
            ], 422);
        }
    }
    public function show($id)
    {
        // Fetch a single product with its associated category
        $category = Category::select(['id', 'name'])->findOrFail($id);

        if ($category) {
            // If the product is found
            return response()->json([
                'message' => 'Category with ID ' . $id . ' has been found.',
                'category' => $category
            ], 200);
        } else {
            // If the product is not found
            return response()->json([
                'message' => 'We could not find the category with ID ' . $id,
            ], 404);
        }
    }


    public function update(Request $request, string $id)
    {
        $category = Category::find($id);
        if ($category) {

            $input = $request->validate([
                'name' => ['required'],
            ]);

            $category->name = $input['name'];


            if ($category->save()) {
                return response()->json([
                    'message' => 'Category with ID ' . $id .  ' updated with success to category',
                    'category' => $category

                ], 200);
            } else {
                return response([
                    'message' =>  'Category with ID ' . $id . ' could not be updated.',
                ], 422);
            }
        } else {

            return response([
                'message' => 'This category  with ID ' . $id . 'can not be found',
            ], 404);
        }
    }


    //Quering product By category  and brand
    public function queryCategories()
    {
        $categoryId = request()->get('category_id');
        $brandId = request()->get('brand_id');

        if ($categoryId || $brandId) {
            // Fetch products by category ID and/or brand ID
            $query = Products::query();

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            if ($brandId) {
                $query->where('brand_id', $brandId);
            }

            // Include the related category, brand, and discounts in the results
            $products = $query->with(['category', 'brand', 'discounts'])->get();

            // Hide created_at and updated_at fields
            $products->makeHidden(['created_at', 'updated_at']);

            // Transform the response to include discount details
            $responseProducts = $products->map(function ($product) {
                $product->discount = $product->discounts->first();
                unset($product->discounts);
                return $product;
            });

            return response()->json([
                'category' => $responseProducts,
            ]);
        } else {
            // Handle the case where no category ID or brand ID is provided
            return response()->json([
                'message' => 'Category ID or Brand ID is required',
            ], 422);
        }
    }


    public function queryMultipleCategories()
    {
        $categoryIds = request()->get('category_id');
        $brandIds = request()->get('brand_id');
        $hasDiscount = request()->get('has_discount');
        $isLatest = request()->get('is_latest');

        // Initialize query builder for Products
        $query = Products::query();

        // Filter by category IDs if provided
        if ($categoryIds) {
            $categoryIdsArray = explode(',', $categoryIds);
            $query->whereIn('category_id', $categoryIdsArray);
        }

        // Filter by brand IDs if provided
        if ($brandIds) {
            $brandIdsArray = explode(',', $brandIds);
            $query->whereIn('brand_id', $brandIdsArray);
        }

        // Filter products that have discounts
        if ($hasDiscount) {
            $query->whereHas('discounts', function ($q) {
                $q->where('end_date', '>=', now())
                    ->orWhereNull('end_date');
            });

            // Join with discounts table to sort by discount percentage
            $query->leftJoin('discounts', 'products.id', '=', 'discounts.product_id')
                ->orderBy('discounts.percentage', 'desc');
        }

        // Filter latest products (assume `is_new_arrival` is a field that indicates latest products)
        if ($isLatest) {
            $query->where('is_new_arrival', true);
        }

        // Select products.* to avoid column conflicts due to join
        $query->select('products.*');

        // Include related category, brand, and discounts in the results
        $products = $query->with(['category', 'brand', 'discounts'])->get();

        // Hide created_at and updated_at fields
        $products->makeHidden(['created_at', 'updated_at']);

        // Transform the response to include discount details
        $responseProducts = $products->map(function ($product) {
            $product->discount = $product->discounts->first();
            unset($product->discounts);
            return $product;
        });

        return response()->json([
            'product_filter' => $responseProducts,
        ], 200);
    }



    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }
        $category->delete();
        return response()->json([
            'message' => 'Category deleted successfully',
        ], 200);
    }
}
