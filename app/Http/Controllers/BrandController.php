<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        $brand = Brand::select('id', 'name', 'logo_url')->get();
        if ($brand) {

            return response()->json([
                'message' => 'Lists of all brand',
                'brand' => $brand,

            ], 200);
        } else {

            return response([
                'message: ' => 'There is no brand on the list',
            ], 204);
        }
    }



    public function store(Request $request)
    {
        $input =  $request->validate([
            'name' => 'required',
            'logo_url' => 'required',
        ]);

        $brand = Brand::create($input);
        if ($brand->save()) {
            return response()->json([
                'message' => 'Success!',
                'brand' => $brand
            ], 200);
        } else {

            return response([
                'message' => 'Brand is failed to create!',
            ], 422);
        }
    }
    public function show($id)
    {
        // Fetch a single product with its associated category
        $brand = Brand::select(['id', 'name', 'logo_url'])->findOrFail($id);
        if ($brand) {
            // If the product is found
            return response()->json([
                'message' => 'Brand with ID ' . $id . ' has been found.',
                'brand' => $brand
            ], 200);
        } else {
            // If the product is not found
            return response()->json([
                'message' => 'We could not find the brand with ID ' . $id,
            ], 404);
        }
    }


    public function update(Request $request, string $id)
    {
        $brand = Brand::find($id);
        if ($brand) {

            $input = $request->validate([
                'name' => ['required'],
                'logo_url' => ['required'],
            ]);

            $brand->name = $input['name'];
            $brand->logo_url = $input['logo_url'];


            if ($brand->save()) {
                return response()->json([
                    'message: ' => 'Brand with ID ' . $id .  ' updated with success to brand',
                    'brand  ' => $brand

                ], 200);
            } else {
                return response([
                    'message' => 'Brand with ID ' . $id . ' could not be updated.',
                ], 422);
            }
        } else {

            return response([
                'message' => 'This brand with ID ' . $id . 'can not be found',
            ], 404);
        }
    }


    public function destroy($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json([
                'message' => 'Brand not found',
            ], 404);
        }
        $brand->delete();
        return response()->json([
            'message' => 'Brand deleted successfully',
        ], 200);
    }
}
