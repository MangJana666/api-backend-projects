<?php

namespace App\Http\Controllers;

use Log;
use Exception;
use Throwable;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = auth()->user();

            $stories = Story::all();
    
            if ($stories->isEmpty()) {
                return response()->json([
                    'message' => 'No stories found'
                ], 404);
            }
    
            return response()->json([
                'stories' => $stories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve stories',
                'error' => $e->getMessage()
            ], 500);
        }
    }    

    public function show($id)
    {
        try {
            $stories = Story::find($id);
    
            if (!$stories) {
                return response()->json([
                    'message' => 'Story not found'
                ], 404);
            }
    
            return response()->json([
                'story' => $stories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve story',
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
            $user = auth()->user();

             $validateata = $request->validate([
                 'title' => 'required',
                 'content' => 'required',
                 'images_cover' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                 'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                 'category_id' => 'required|exists:categories,id',
             ]);
     
             if (!$user) {
                 return response()->json(['message' => 'User not authenticated'], 401);
             }
     
             $coverImagePath = null;
             if ($request->hasFile('images_cover')) {
                 $coverImage = $request->file('images_cover');
                 $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
                 $coverImage->storeAs('public/cover', $coverImagePath);
             }
     
             $stories = Story::create([
                 'title' => $validateData['title'],
                 'content' => $validateData['content'],
                 'images_cover' => $coverImagePath,
                 'category_id' => $validateData['category_id'],
                 'user_id' => $user->id,
             ]);
     
             if ($request->hasFile('images')) {
                 foreach ($request->file('images') as $image) {
                     $imagePath = time() . '_' . $image->getClientOriginalName();
                     $image->storeAs('public/images', $imagePath);
                     $stories->images()->create(['filename' => $imagePath]);
                 }
             }
     
             return response()->json([
                 'message' => 'Story created successfully',
                 'story' => $stories
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
    // public function update(Request $request, string $id)
    // {
    //     try {
    //         $user = auth()->user();
    
    //         if (!$user) {
    //             return response()->json([
    //                 'message' => 'User not authenticated',
    //             ], 401);
    //         }
    
    //         $validateData = $request->validate([
    //             'title' => 'sometimes|unique:stories,title,' . $id . '|max:255',
    //             'content' => 'sometimes',
    //             'images_cover' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //             'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //             'category_id' => 'sometimes|exists:categories,id',
    //         ], [
    //             'title.required' => 'Title is required',
    //             'title.unique' => 'Story title must be unique',
    //             'content.required' => 'Content is required',
    //             'category_id.required' => 'Category is required',
    //             'category_id.exists' => 'Category does not exist',
    //         ]);
    
    //         $stories = Story::find($id);
    
    //         if (!$stories) {
    //             return response()->json([
    //                 'message' => 'Story not found',
    //             ], 404);
    //         }
    
    //         $coverImagePath = $stories->images_cover;
    //         if ($request->hasFile('images_cover')) {
    //             $coverImage = $request->file('images_cover');
    //             $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
    //             $coverImage->storeAs('public/cover', $coverImagePath);
    //         }
    
    //         $stories->update([
    //             'title' => $validateData['title'],
    //             'content' => $validateData['content'],
    //             'images_cover' => $coverImagePath,
    //             'category_id' => $validateData['category_id'],
    //         ]);
    
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $image) {
    //                 $imagePath = time() . '_' . $image->getClientOriginalName();
    //                 $image->storeAs('public/images', $imagePath);
    //                 $stories->images()->create(['filename' => $imagePath]);
    //             }
    //         }
    
    //         return response()->json([
    //             'message' => 'Story updated successfully',
    //             'story' => $stories->load('images'),
    //         ], 200);
    
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to update story',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

//     public function update(Request $request, string $id)
// {
//     try {
//         $user = auth()->user();

//         if (!$user) {
//             return response()->json([
//                 'message' => 'User not authenticated',
//             ], 401);
//         }

//         $validatedData = $request->validate([
//             'title' => 'sometimes|required|unique:stories,title,' . $id . '|max:255',
//             'content' => 'sometimes|required',
//             'images_cover' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//             'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//             'category_id' => 'sometimes|required|exists:categories,id',
//         ], [
//             'title.required' => 'Title is required',
//             'title.unique' => 'Story title must be unique',
//             'content.required' => 'Content is required',
//             'category_id.required' => 'Category is required',
//             'category_id.exists' => 'Category does not exist',
//         ]);

//         $story = Story::find($id);

//         if (!$story) {
//             return response()->json([
//                 'message' => 'Story not found',
//             ], 404);
//         }

//         // Handle cover image upload if present
//         $coverImagePath = $story->images_cover;
//         if ($request->hasFile('images_cover')) {
//             $coverImage = $request->file('images_cover');
//             $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
//             $coverImage->storeAs('public/cover', $coverImagePath);
//         }

//         // Prepare update data
//         $updateData = [];
//         if (isset($validatedData['title'])) {
//             $updateData['title'] = $validatedData['title'];
//         }
//         if (isset($validatedData['content'])) {
//             $updateData['content'] = $validatedData['content'];
//         }
//         if (isset($validatedData['category_id'])) {
//             $updateData['category_id'] = $validatedData['category_id'];
//         }
//         if ($coverImagePath !== $story->images_cover) {
//             $updateData['images_cover'] = $coverImagePath;
//         }

//         // Update the story with provided data
//         $story->update($updateData);

//         // Handle multiple images upload if present
//         if ($request->hasFile('images')) {
//             $maxImages = 3; // Define the maximum number of images allowed
//             $currentImages = $story->images()->orderBy('created_at', 'asc')->get();

//             // Remove oldest images if necessary
//             while ($currentImages->count() >= $maxImages) {
//                 $oldestImage = $currentImages->shift();
//                 Storage::disk('public')->delete('images/' . $oldestImage->filename);
//                 $oldestImage->delete();
//             }

//             foreach ($request->file('images') as $image) {
//                 $imagePath = time() . '_' . $image->getClientOriginalName();
//                 $image->storeAs('public/images', $imagePath);
//                 $story->images()->create(['filename' => $imagePath]);
//             }
//         }

//         // Reload the story to ensure we get the latest data
//         $story->load('images');

//         return response()->json([
//             'message' => 'Story updated successfully',
//             'story' => $story,
//         ], 200);

//     } catch (\Illuminate\Validation\ValidationException $e) {
//         return response()->json([
//             'message' => 'Validation failed',
//             'errors' => $e->errors(),
//         ], 422);
//     } catch (\Exception $e) {
//         \Log::error('Error updating story: ' . $e->getMessage(), [
//             'trace' => $e->getTraceAsString()
//         ]);
//         return response()->json([
//             'message' => 'Failed to update story',
//             'error' => $e->getMessage(),
//         ], 500);
//     }
// }

    public function update(Request $request, string $id)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated',
                ], 401);
            }

            $validateData = $request->validate([
                'title' => 'sometimes|unique:stories,title,' . $id . '|max:255',
                'content' => 'sometimes',
                'images_cover' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'category_id' => 'sometimes|exists:categories,id',
            ]);

            $stories = Story::findOrFail($id); // Ensure the story exists or return 404

            $coverImagePath = $stories->images_cover;
            if ($request->hasFile('images_cover')) {
                // Delete old cover image if exists
                if (File::exists('storage/cover/' . $stories->images_cover)) {
                    File::delete('storage/cover/' . $stories->images_cover);
                }

                $coverImage = $request->file('images_cover');
                $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
                $coverImage->storeAs('public/cover', $coverImagePath);
            }

            // Update story fields
            $stories->update([
                'title' => $request->title ?? $stories->title,
                'content' => $request->content ?? $stories->content,
                'images_cover' => $coverImagePath,
                'category_id' => $request->category_id ?? $stories->category_id,
            ]);

            // Handle additional images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePath = time() . '_' . $image->getClientOriginalName();
                    $image->storeAs('public/images', $imagePath);
                    $stories->images()->create(['filename' => $imagePath]);
                }
            }

            return response()->json([
                'message' => 'Story updated successfully',
                'story' => $stories->load('images'),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update story',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    

      
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
