<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\Discount;
use Illuminate\Http\Request;
use App\Http\Resources\DiscountResource;

class DiscountController extends Controller
{

    public function index()
    {
        $discounts = Discount::with('product')->orderBy('percentage', 'desc')->get();
        return response()->json([
            'message' => 'success',
            'discounts' => DiscountResource::collection($discounts)
        ], 200);
    }
    public function attachDiscount(Request $request, $productId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $product = Products::findOrFail($productId);

        // Check if a discount already exists for this product
        $existingDiscount = Discount::where('product_id', '=', $productId)->first();
        if ($existingDiscount) {
            return response()->json([
                'message' => 'A discount already exists for this product.',
                'discount' => $existingDiscount->load('product') // Load the associated product
            ], 409); // 409 Conflict status code
        }

        // Create a new discount
        $discount = new Discount([
            'name' => $request->name,
            'percentage' => (float)$request->percentage,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'product_id' => $productId
        ]);

        $discount->save();

        return response()->json([
            'message' => 'Discount attached to product successfully.',
            'discount' => $discount->load('product') // Load the associated product
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

        $discount = Discount::find($id);
        if (!$discount) {
            return response()->json(['message' => 'Discount not found'], 404);
        }

        $discount->update(['percentage' => $request->percentage]);

        return response()->json([
            'message' => 'Discount updated successfully',
            'discount' => new DiscountResource($discount)
        ], 200);
    }



    public function destroy($id)
    {
        $discount = Discount::find($id);
        if (!$discount) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }
        $discount->delete();
        return response()->json([
            'message' => 'Category deleted successfully',
        ], 200);
    }
}
