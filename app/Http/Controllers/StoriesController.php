<?php

namespace App\Http\Controllers;

use Log;
use Exception;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stories = Story::all();
        return response()->json([
            'stories' => $stories
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */

     public function store(Request $request)
     {
         try {
             $validatedData = $request->validate([
                 'title' => 'required',
                 'content' => 'required',
                 'images_cover' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                 'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                 'category_id' => 'required|exists:categories,id',
             ]);
     
             $user = Auth::user();
     
             if (!$user) {
                 return response()->json(['message' => 'User not authenticated'], 401);
             }
     
             $coverImagePath = null;
             if ($request->hasFile('images_cover')) {
                 $coverImage = $request->file('images_cover');
                 $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
                 $coverImage->storeAs('public/cover', $coverImagePath);
             }
     
             $story = Story::create([
                 'title' => $validatedData['title'],
                 'content' => $validatedData['content'],
                 'images_cover' => $coverImagePath,
                 'category_id' => $validatedData['category_id'],
                 'user_id' => $user->id,
             ]);
     
             if ($request->hasFile('images')) {
                 foreach ($request->file('images') as $image) {
                     $imagePath = time() . '_' . $image->getClientOriginalName();
                     $image->storeAs('public/images', $imagePath);
                     $story->images()->create(['filename' => $imagePath]);
                 }
             }
     
             return response()->json([
                 'message' => 'Story created successfully',
                 'story' => $story->load('user'),
             ], 201);
         } catch (\Exception $e) {
             Log::error('Error creating story: ' . $e->getMessage(), [
                 'trace' => $e->getTraceAsString()
             ]);
             return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
         }
     }
     
    
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $stories = Story::find($id);

        if($stories){
            return response()->json([
                'stories' => $stories
            ], 200);
        }else{
            return response()->json([
                'message' => 'Story not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validateData = $request->validate([
            'title' => 'required',
            'content' => 'required',
            'images_cover' => 'required',
            'category_id' => 'required',
        ], [
            'title.required' => 'Title is required',
            'content.required' => 'Content is required',
            'images_cover.required' => 'Images cover is required',
            'category_id.required' => 'Category ID is required',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
