<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Products;
use App\Http\Resources\ProductResource;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $products = Products::with(['category', 'brand', 'discounts'])
            ->select(['id', 'name', 'category_id', 'brand_id', 'price', 'images', 'description', 'is_new_arrival'])
            ->get();

        if ($products->isNotEmpty()) {
            return response()->json([
                'message' => 'List of all products',
                'product' => ProductResource::collection($products),
            ], 200);
        } else {
            return response()->json([
                'message' => 'There are no products in the list',
            ], 204);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $input = $request->validate([
            'name' => 'required',
            'category_id' => 'required',
            'brand_id' => 'required',
            'price' => 'required',
            'images' => 'required',
            'description' => 'required',
        ]);

        $input['is_new_arrival'] = $request->input('is_new_arrival', true);

        $product = Products::create($input);

        return response()->json([
            'message' => $product->wasRecentlyCreated ? 'Success!' : 'Product creation failed!',
            'product' => $product->wasRecentlyCreated ? new ProductResource($product) : null,
        ], $product->wasRecentlyCreated ? 200 : 422);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Products::with(['category', 'brand', 'discounts'])
            ->select(['id', 'name', 'category_id', 'brand_id', 'price', 'images', 'description', 'is_new_arrival'])
            ->find($id);

        if ($product) {
            return response()->json([
                'message' => 'Product with ID ' . $id . ' has been found.',
                'product' => new ProductResource($product),
            ], 200);
        } else {
            return response()->json([
                'message' => 'We could not find the product with ID ' . $id,
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Products::find($id);
        if ($product) {
            $input = $request->validate([
                'name' => ['required'],
                'category_id' => ['required'],
                'brand_id' => ['required'],
                'price' => ['required'],
                'images' => ['required'],
                'description' => ['required'],
                'is_new_arrival' => 'boolean'
            ]);

            $product->name = $input['name'];
            $product->category_id = $input['category_id'];
            $product->brand_id = $input['brand_id'];
            $product->price = $input['price'];
            $product->images = $input['images'];
            $product->description = $input['description'];
            if (isset($input['is_new_arrival'])) {
                $product->is_new_arrival = $input['is_new_arrival'];
            }

            if ($product->save()) {
                return response()->json([
                    'message' => 'Product with ID ' . $id . ' updated successfully',
                    'product' => $product
                ], 200);
            } else {
                return response([
                    'message' => 'Product with ID ' . $id . ' could not be updated.',
                ], 422);
            }
        } else {
            return response([
                'message' => 'Product with ID ' . $id . ' cannot be found',
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Products::find($id);
        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }
        $product->delete();
        return response()->json([
            'message' => 'Product deleted successfully',
        ], 200);
    }
}
