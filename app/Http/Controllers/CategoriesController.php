<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required|unique:categories,name|max:50',
        ], [
            'name.required' => 'Name is required',
        ]);

        $categories = Category::create($validateData);

        if(!$categories){
            return response()->json([
                'message' => 'Category not created'
            ], 400);
        }else{
            return response()->json([
                'message' => 'Category created successfully',
            ], 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $categories = Category::find($id);

        if($categories){
            return response()->json([
                'categories' => $categories
            ], 200);
        }else{
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validateData = $request->validate([
            'name' => 'required|unique:categories,name|max:50',
        ], [
            'name.required' => 'Name is required',
        ]);

        $categories = Category::find($id);
        if(!$categories){
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }

        $categories->update($validateData);
        return response()->json([
            'message' => 'Category updated successfully'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
