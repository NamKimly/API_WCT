<?php

namespace App\Http\Controllers;

use App\Models\Products;
use Illuminate\Http\Request;
use App\Models\Promotion;

class PromotionController extends Controller
{
    public function index()
    {
        // Retrieve promotions with associated products, categories, and brands
        $promotions = Promotion::with(['products.category', 'products.brand'])->get();

        // Iterate through the promotions to calculate the total price of non-free products
        foreach ($promotions as $promotion) {
            $totalPrice = 0;
            foreach ($promotion->products as $product) {
                if ($product->pivot->is_free == 0) {
                    $totalPrice += $product->price;
                }
            }
            // Add the total price to the promotion object
            $promotion->total_price = (int) $totalPrice;
        }

        // Return the response with the promotions and their calculated total prices
        return response()->json([
            'message' => 'successfully',
            'promotion_products' => $promotions
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.is_free' => 'required|boolean',
        ]);

        $promotion = Promotion::create($request->only('name', 'description'));

        foreach ($request->products as $product) {
            $promotion->products()->attach($product['product_id'], ['is_free' => $product['is_free']]);
        }

        return response()->json(['message' => 'Promotion created successfully'], 201);
    }

    public function show($id)
    {
        // Retrieve the promotion by ID with its related products, category, and brand
        $promotion = Promotion::with(['products.category', 'products.brand'])->findOrFail($id);

        // Initialize total price of non-free products
        $totalPrice = 0;

        // Calculate the total price of non-free products
        foreach ($promotion->products as $product) {
            if ($product->pivot->is_free == 0) {
                $totalPrice += $product->price;
            }
        }

        // Add the total price to the promotion object (optional)
        $promotion->total_price = (int) $totalPrice;

        // Return the response with the calculated total price and promotion details
        return response()->json([
            'message' => 'successfully',
            'promotion_products' => $promotion
        ], 200);
    }



    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.is_free' => 'required|boolean',
        ]);

        // Find the promotion by ID or fail
        $promotion = Promotion::findOrFail($id);

        // Update the promotion with the provided name and description
        $promotion->update($request->only('name', 'description'));

        // Detach all existing products
        $promotion->products()->detach();

        // Initialize total price of non-free products
        $totalPrice = 0;

        // Attach the new products and calculate the total price for non-free products
        foreach ($request->products as $product) {
            $promotion->products()->attach($product['product_id'], ['is_free' => $product['is_free']]);

            if ($product['is_free'] == 0) {
                $productData = Products::findOrFail($product['product_id']);
                $totalPrice += $productData->price;
            }
        }

        // Add the total price to the promotion object (optional)
        $promotion->total_price_non_free_products = $totalPrice;

        // Return the response
        return response()->json([
            'message' => 'Promotion updated successfully',
            'total_price_non_free_products' => $totalPrice,
            'promotion' => $promotion->load('products')
        ], 200);
    }



    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->delete();

        return response()->json(['message' => 'Promotion deleted successfully'], 200);
    }
}
