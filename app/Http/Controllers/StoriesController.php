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
     
             $validateData = $request->validate([
                 'title' => 'required',
                 'content' => 'required',
                //  'images_cover' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                 'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                 'category_id' => 'required|exists:categories,id',
             ]);
     
             if (!$user) {
                 return response()->json(['message' => 'User not authenticated'], 401);
             }
     
             // Handle cover image upload
            //  $coverImagePath = null;
            //  if ($request->hasFile('images_cover')) {
            //      $coverImage = $request->file('images_cover');
            //      $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
            //      $coverImage->storeAs('public/cover', $coverImagePath);
            //  }
     
             // Handle multiple images upload
             $imagePaths = [];
             if ($request->hasFile('images')) {
                 foreach ($request->file('images') as $image) {
                     $imagePath = time() . '_' . $image->getClientOriginalName();
                     $image->storeAs('public/images', $imagePath);
                     $imagePaths[] = $imagePath; // Add to image paths array
                 }
             }
     
             // Create the story
             $story = Story::create([
                 'title' => $validateData['title'],
                 'content' => $validateData['content'],
                //  'images_cover' => $coverImagePath,
                 'images' => json_encode($imagePaths), // Save as JSON
                 'category_id' => $validateData['category_id'],
                 'user_id' => $user->id,
             ]);
     
             return response()->json([
                 'message' => 'Story created successfully',
                 'story' => $story,
             ], 201);
         } catch (\Exception $e) {
             \Log::error('Error creating story: ' . $e->getMessage(), [
                 'trace' => $e->getTraceAsString(),
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
    //         ]);

    //         $stories = Story::findOrFail($id); // Ensure the story exists or return 404

    //         $coverImagePath = $stories->images_cover;
    //         if ($request->hasFile('images_cover')) {
    //             // Delete old cover image if exists
    //             if (File::exists('storage/cover/' . $stories->images_cover)) {
    //                 File::delete('storage/cover/' . $stories->images_cover);
    //             }

    //             $coverImage = $request->file('images_cover');
    //             $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
    //             $coverImage->storeAs('public/cover', $coverImagePath);
    //         }

    //         // Update story fields
    //         $stories->update([
    //             'title' => $validateData['title'],
    //             'content' => $validateData['content'],
    //             'images_cover' => $coverImagePath,
    //             'category_id' => $validateData['category_id'],
    //         ]);

    //         // Handle additional images
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

    // public function update(Request $request, string $id)
    // {
    //     try {
    //         // Add debugging logs
    //         \Log::info('Raw request data:', [
    //             'all' => $request->all(),
    //             'files' => $request->allFiles(),
    //             'has title' => $request->has('title'),
    //             'filled title' => $request->filled('title'),
    //             'input title' => $request->input('title')
    //         ]);
    
    //         $user = auth()->user();
    //         if (!$user) {
    //             return response()->json(['message' => 'User not authenticated'], 401);
    //         }
    
    //         $story = Story::findOrFail($id);
    
    //         // Build update data with direct checking
    //         $updateData = [];
            
    //         // Check each field explicitly
    //         if ($request->input('title') !== null) {
    //             $updateData['title'] = $request->input('title');
    //         }
            
    //         if ($request->input('content') !== null) {
    //             $updateData['content'] = $request->input('content');
    //         }
            
    //         if ($request->input('category_id') !== null) {
    //             $updateData['category_id'] = $request->input('category_id');
    //         }
    
    //         // Handle cover image
    //         if ($request->hasFile('images_cover')) {
    //             $coverImage = $request->file('images_cover');
    //             $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
    //             $coverImage->storeAs('public/cover', $coverImagePath);
    //             $updateData['images_cover'] = $coverImagePath;
    //         }
    
    //         // Log the update data
    //         \Log::info('Update data:', $updateData);
    
    //         // Only perform update if we have data to update
    //         if (!empty($updateData)) {
    //             $story->update($updateData);
    //         }
    
    //         // Handle multiple images
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $image) {
    //                 $imagePath = time() . '_' . $image->getClientOriginalName();
    //                 $image->storeAs('public/images', $imagePath);
    //                 $story->images()->create([
    //                     'filename' => $imagePath
    //                 ]);
    //             }
    //         }
    
    //         return response()->json([
    //             'message' => 'Story updated successfully',
    //             'story' => $story->fresh()->load('images'),
    //             'updated_fields' => array_keys($updateData),
    //             'received_data' => $request->all() // Add this to see what data was received
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         \Log::error('Update error:', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         return response()->json([
    //             'message' => 'Failed to update story',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function update(Request $request, string $id)
    // {
    //     try {
    //         $user = auth()->user();
    //         if (!$user) {
    //             return response()->json(['message' => 'User not authenticated'], 401);
    //         }

    //         // Find story or fail
    //         $story = Story::findOrFail($id);

    //         // Basic validation
    //         $validated = $request->validate([
    //             'title' => 'sometimes|string|max:255',
    //             'content' => 'sometimes|string',
    //             'category_id' => 'sometimes|exists:categories,id',
    //             'images_cover' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //             'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
    //         ]);

    //         // Handle basic fields update
    //         if ($request->has('title')) {
    //             $story->title = $request->title;
    //         }
    //         if ($request->has('content')) {
    //             $story->content = $request->content;
    //         }
    //         if ($request->has('category_id')) {
    //             $story->category_id = $request->category_id;
    //         }

    //         // Handle cover image
    //         if ($request->hasFile('images_cover')) {
    //             // Delete old cover if exists
    //             if ($story->images_cover) {
    //                 Storage::delete('public/cover/' . $story->images_cover);
    //             }
                
    //             $coverImage = $request->file('images_cover');
    //             $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
    //             $coverImage->storeAs('public/cover', $coverImagePath);
    //             $story->images_cover = $coverImagePath;
    //         }

    //         // Save the story changes
    //         $story->save();

    //         // Handle multiple images
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $image) {
    //                 $imagePath = time() . '_' . $image->getClientOriginalName();
    //                 $image->storeAs('public/images', $imagePath);
                    
    //                 // Create new image record
    //                 $story->images()->create([
    //                     'filename' => $imagePath
    //                 ]);
    //             }
    //         }

    //         // Return updated story with images
    //         return response()->json([
    //             'message' => 'Story updated successfully',
    //             'story' => $story->fresh()->load('images')
    //         ], 200);

    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['message' => 'Story not found'], 404);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors()
    //         ], 422);
    //     } catch (Exception $e) {
    //         \Log::error('Story update error: ' . $e->getMessage());
    //         return response()->json([
    //             'message' => 'Failed to update story',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function update(Request $request, string $id)
    // {
    //     try {
    //         $user = auth()->user();
    
    //         if (!$user) {
    //             return response()->json([
    //                 'message' => 'User not authenticated',
    //             ], 401);
    //         }
    
    //         $validatedData = $request->validate([
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
    
    //         // Handle images_cover
    //         $coverImagePath = $stories->images_cover;
    //         if ($request->hasFile('images_cover')) {
    //             $coverImage = $request->file('images_cover');
    //             $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
    //             $coverImage->storeAs('public/cover', $coverImagePath);
    //         }
    
    //         // Update fields if they exist
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
    //         $updateData['images_cover'] = $coverImagePath;
    
    //         $stories->update($updateData);
    
    //         // Handle additional images
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

    // public function update(Request $request, string $id) //ne nganggo id image liak
    // {
    //     try {
    //         // Validasi pengguna yang login
    //         $user = auth()->user();
    //         if (!$user) {
    //             return response()->json([
    //                 'message' => 'User not authenticated',
    //             ], 401);
    //         }
    
    //         // Validasi data
    //         $validatedData = $request->validate([
    //             'title' => 'sometimes|unique:stories,title,' . $id . ',id|max:255',
    //             'content' => 'sometimes',
    //             'images_cover' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //             'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //             'category_id' => 'sometimes|exists:categories,id',
    //             '_removeImages' => 'array', // Validasi untuk array ID gambar
    //             '_removeImages.*' => 'exists:images,id', // Validasi ID gambar harus ada di database
    //         ], [
    //             'title.required' => 'Title is required',
    //             'title.unique' => 'Story title must be unique',
    //             'content.required' => 'Content is required',
    //             'category_id.required' => 'Category is required',
    //             'category_id.exists' => 'Category does not exist',
    //             '_removeImages.*.exists' => 'Image to be removed does not exist.',
    //         ]);
    
    //         // Cari story berdasarkan ID
    //         $story = Story::find($id);
    //         if (!$story) {
    //             return response()->json([
    //                 'message' => 'Story not found',
    //             ], 404);
    //         }
    
    //         $updateData = [];
    
    //         // Perbarui title jika ada
    //         if (isset($validatedData['title'])) {
    //             $updateData['title'] = $validatedData['title'];
    //         }
    
    //         // Perbarui content jika ada
    //         if (isset($validatedData['content'])) {
    //             $updateData['content'] = $validatedData['content'];
    //         }
    
    //         // Perbarui category_id jika ada
    //         if (isset($validatedData['category_id'])) {
    //             $updateData['category_id'] = $validatedData['category_id'];
    //         }
    
    //         // Perbarui cover image jika ada file yang diupload
    //         $coverImagePath = $story->images_cover;
    //         if ($request->hasFile('images_cover')) {
    //             $coverImage = $request->file('images_cover');
    //             $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
    //             $coverImage->storeAs('public/cover', $coverImagePath);
    //             $updateData['images_cover'] = $coverImagePath;
    //         }
    
    //         // Perbarui data cerita
    //         $story->update($updateData);
    
    //         // Hapus gambar berdasarkan ID di _removeImages[]
    //         if ($request->has('_removeImages')) {
    //             $imageIds = $validatedData['_removeImages'];
    
    //             foreach ($imageIds as $imageId) {
    //                 $image = $story->images()->find($imageId);
    
    //                 if ($image) {
    //                     // Hapus file gambar dari storage
    //                     Storage::delete('public/images/' . $image->filename);
    
    //                     // Hapus data gambar dari database
    //                     $image->delete();
    //                 }
    //             }
    //         }
    
    //         // Tambahkan gambar baru jika diupload
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $image) {
    //                 $imagePath = time() . '_' . $image->getClientOriginalName();
    //                 $image->storeAs('public/images', $imagePath);
    //                 $story->images()->create(['filename' => $imagePath]);
    //             }
    //         }
    
    //         return response()->json([
    //             'message' => 'Story updated successfully',
    //             'story' => $story->load('images'),
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

    public function update(Request $request, string $id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }
    
            $validatedData = $request->validate([
                'title' => 'sometimes|max:255',
                'content' => 'sometimes',
                // 'images_cover' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'category_id' => 'sometimes|exists:categories,id',
            ]);
    
            $story = Story::findOrFail($id);
    
            if ($request->has('title')) {
                $story->title = $validatedData['title'];
            }
    
            if ($request->has('content')) {
                $story->content = $validatedData['content'];
            }
    
            if ($request->has('category_id')) {
                $story->category_id = $validatedData['category_id'];
            }
    
            // if ($request->hasFile('images_cover')) {
            //     if ($story->images_cover) {
            //         Storage::delete('public/cover/' . $story->images_cover);
            //     }
    
            //     $coverImage = $request->file('images_cover');
            //     $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
            //     $coverImage->storeAs('public/cover', $coverImagePath);
            //     $story->images_cover = $coverImagePath;
            // }
    
            $imagePaths = $story->images ? json_decode($story->images, true) : [];
    
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePath = time() . '_' . $image->getClientOriginalName();
                    $image->storeAs('public/images', $imagePath);
                    $imagePaths[] = $imagePath; // Add new image path
                }
            }
    
            $story->images = json_encode($imagePaths);
            $story->save();
    
            return response()->json([
                'message' => 'Story updated successfully',
                'story' => $story,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
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
