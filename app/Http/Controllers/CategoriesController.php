<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $categories = Category::all();
            return response()->json([
                'categories' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validateData = $request->validate([
                'name' => 'required|unique:categories,name|max:50',
            ], [
                'name.required' => 'Name is required',
                'name.unique' => 'Category name must be unique',
            ]);

            $categories = Category::create($validateData);

            return response()->json([
                'message' => 'Category created successfully',
                'category' => $categories
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $category = Category::find($id);

            if ($category) {
                return response()->json([
                    'category' => $category
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validateData = $request->validate([
                'name' => 'required|unique:categories,name|max:50',
            ], [
                'name.required' => 'Name is required',
                'name.unique' => 'Category name must be unique',
            ]);

            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            $category->update($validateData);

            return response()->json([
                'message' => 'Category updated successfully',
                'category' => $category
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
