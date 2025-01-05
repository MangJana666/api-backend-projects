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
    // public function index()
    // {
    //     try {
    //         $stories = Story::with(['images', 'category']);
    
    //         if ($stories->isEmpty()) {
    //             return response()->json([
    //                 'message' => 'No stories found'
    //             ], 404);
    //         }
    //         $formattedStories = $stories->map(function($story) {
    //             return [
    //                 'id' => $story->id,
    //                 'title' => $story->title,
    //                 'content' => $story->content,
    //                 'category' => [
    //                     'name' => $story->category->name
    //                 ],
    //                 'user' => [
    //                     'id' => $story->user->id,
    //                     'name' => $story->user->name
    //                 ],
    //                 'images' => $story->images->map(function($image) {
    //                     return [
    //                         'id' => $image->id,
    //                         'filename' => $image->filename,
    //                         'url' => asset('storage/' . $image->filename)
    //                     ];
    //                 })
    //             ];
    //         });
    
    //         return response()->json([
    //             'status' => true,
    //             'data' => [
    //                 'stories' => $formattedStories
    //             ]
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to retrieve stories',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function allStories()
    {
        try {
            $stories = Story::with(['images', 'category', 'user'])->get();
    
            if ($stories->isEmpty()) {
                return response()->json([
                    'message' => 'No stories found'
                ], 404);
            }
    
            $formattedStories = $stories->map(function($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'category' => [
                        'name' => $story->category->name
                    ],
                    'user' => [
                        'id' => $story->user->id,
                        'name' => $story->user->name
                    ],
                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset('storage/' . $image->filename)
                        ];
                    })
                ];
            });
    
            return response()->json([
                'status' => true,
                'data' => [
                    'stories' => $formattedStories
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve stories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storiesByCategory($categoryId)
    {
        try {
            $stories = Story::with(['images', 'category', 'user'])
                ->where('category_id', $categoryId)
                ->get();
    
            if ($stories->isEmpty()) {
                return response()->json([
                    'message' => 'No stories found in this category'
                ], 404);
            }
    
            $formattedStories = $stories->map(function($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'category' => [
                        'name' => $story->category->name
                    ],
                    'user' => [
                        'id' => $story->user->id,
                        'name' => $story->user->name
                    ],
                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset('storage/' . $image->filename)
                        ];
                    })
                ];
            });
    
            return response()->json([
                'status' => true,
                'data' => [
                    'stories' => $formattedStories
                ]
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
            // Eager load images relationship
            $user = auth()->user();
            $story = Story::with(['images', 'user', 'category'])->find($id);
    
            if (!$story) {
                return response()->json([
                    'message' => 'Story not found'
                ], 404);
            }
    
            // Add full URL for each image
            $story->images->transform(function ($image) {
                $image->url = asset('storage/' . $image->filename);
                return $image;
            });
    
            return response()->json([
                'status' => true,
                'data' => [
                    'story' => [
                        'id' => $story->id,
                        'title' => $story->title,
                        'content' => $story->content,
                        'category' => $story->category,
                        'user' => $story->user,
                        'images' => $story->images,
                    ],
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */

    //  public function store(Request $request)
    //  {
    //      try {
    //          $user = auth()->user();
     
    //          $validateData = $request->validate([
    //              'title' => 'required',
    //              'content' => 'required',
    //             //  'images_cover' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //              'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //              'category_id' => 'required|exists:categories,id',
    //          ]);
     
    //          if (!$user) {
    //              return response()->json(['message' => 'User not authenticated'], 401);
    //          }
     
    //          // Handle cover image upload
    //         //  $coverImagePath = null;
    //         //  if ($request->hasFile('images_cover')) {
    //         //      $coverImage = $request->file('images_cover');
    //         //      $coverImagePath = time() . '_' . $coverImage->getClientOriginalName();
    //         //      $coverImage->storeAs('public/cover', $coverImagePath);
    //         //  }
     
    //          // Handle multiple images upload
    //          $imagePaths = [];
    //          if ($request->hasFile('images')) {
    //              foreach ($request->file('images') as $image) {
    //                  $imagePath = time() . '_' . $image->getClientOriginalName();
    //                  $image->storeAs('public/images', $imagePath);
    //                  $imagePaths[] = $imagePath; // Add to image paths array
    //              }
    //          }
     
    //          // Create the story
    //          $story = Story::create([
    //              'title' => $validateData['title'],
    //              'content' => $validateData['content'],
    //             //  'images_cover' => $coverImagePath,
    //              'images' => json_encode($imagePaths), // Save as JSON
    //              'category_id' => $validateData['category_id'],
    //              'user_id' => $user->id,
    //          ]);
     
    //          return response()->json([
    //              'message' => 'Story created successfully',
    //              'story' => $story,
    //          ], 201);
    //      } catch (\Exception $e) {
    //          \Log::error('Error creating story: ' . $e->getMessage(), [
    //              'trace' => $e->getTraceAsString(),
    //          ]);
    //          return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
    //      }
    //  }

    // public function store(Request $request)
    // {
    //     try {
    //         $user = auth()->user();
    
    //         $validateData = $request->validate([
    //             'title' => 'required',
    //             'content' => 'required',
    //             'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //             'category_id' => 'required|exists:categories,id',
    //         ]);
    
    //         if (!$user) {
    //             return response()->json(['message' => 'User not authenticated'], 401);
    //         }
    
    //         // Create the story
    //         $story = Story::create([
    //             'title' => $validateData['title'],
    //             'content' => $validateData['content'],
    //             'category_id' => $validateData['category_id'],
    //             'user_id' => $user->id,
    //         ]);
    
    //         // Handle multiple images upload
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $image) {
    //                 $imagePath = time() . '_' . $image->getClientOriginalName();
    //                 $image->storeAs('public/images', $imagePath);
    //                 $story->images()->create(['filename' => $imagePath]);
    //             }
    //         }
    
    //         return response()->json([
    //             'message' => 'Story created successfully',
    //             'story' => $story->load('images'),
    //         ], 201);
    //     } catch (\Exception $e) {
    //         \Log::error('Error creating story: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
    //     }
    // }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();
    
            // Validate the request data
            $validateData = $request->validate([
                'title' => 'required',
                'content' => 'required',
                'images' => 'array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'category_id' => 'required|exists:categories,id',
            ]);
    
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }
    
            // Create the story
            $story = Story::create([
                'title' => $validateData['title'],
                'content' => $validateData['content'],
                // 'images_cover' => null, // Default null, update if needed
                'category_id' => $validateData['category_id'],
                'user_id' => $user->id,
            ]);
    
            // Handle image uploads
            if ($request->hasFile('images')) {
                $uploadedImages = $request->file('images');
    
                // Ensure a maximum of 5 images
                if (count($uploadedImages) > 5) {
                    return response()->json([
                        'message' => 'You can only upload a maximum of 5 images.',
                    ], 422);
                }
    
                // Process each image
                foreach ($uploadedImages as $index => $image) {
                    $imageName = time() . "_{$index}_" . md5(uniqid(rand(), true)) . '.' . $image->extension();
                    $imagePath = $image->storeAs('images', $imageName, 'public');
                    $story->images()->create(['filename' => "/storage/{$imagePath}"]);
                }
            }
    
            // Load related images for the response
            $story->load('images');
    
            return response()->json([
                'message' => 'Story created successfully',
                'story' => [
                    'data' => $story,
                    'images' => $story->images->map(function ($image) {
                        return $image->filename; // Return the full path of images
                    }),
                ],
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
    //             return response()->json(['message' => 'User not authenticated'], 401);
    //         }
    
    //         // Validate the request data
    //         $validatedData = $request->validate([
    //             'title' => 'sometimes|string|max:255',
    //             'content' => 'sometimes|string',
    //             'category_id' => 'sometimes|exists:categories,id',
    //             'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //             '_removeImages' => 'array|max:5',
    //             '_removeImages.*' => 'exists:images,id'
    //         ]);
    
    //         // Find the story or fail
    //         $story = Story::findOrFail($id);
    
    //         // Update the story fields
    //         if (isset($validatedData['title'])) {
    //             $story->title = $validatedData['title'];
    //         }
    //         if (isset($validatedData['content'])) {
    //             $story->content = $validatedData['content'];
    //         }
    //         if (isset($validatedData['category_id'])) {
    //             $story->category_id = $validatedData['category_id'];
    //         }
    
    //         // Save the story changes
    //         $story->save();
    
    //     // Handle removing images
    //     if ($request->has('_removeImages')) {
    //         foreach ($validatedData['_removeImages'] as $imageId) {
    //             $image = $story->images()->find($imageId);
    //             if ($image) {
    //                 // Delete physical file
    //                 Storage::delete('public/images/' . basename($image->filename));
    //                 $image->delete();
    //             }
    //         }
    //     }

    //     // Handle new image uploads
    //     if ($request->hasFile('images')) {
    //         foreach ($request->file('images') as $image) {
    //             $filename = time() . '_' . $image->getClientOriginalName();
    //             $image->storeAs('public/images', $filename);
                
    //             // Store full path in database
    //             $fullPath = Storage::url('public/images/' . $filename);
    //             $story->images()->create([
    //                 'filename' => $fullPath
    //             ]);
    //         }
    //     }
    
    //         return response()->json([
    //             'message' => 'Story updated successfully',
    //             'story' => $story->load('images'),
    //         ], 200);
    //     } catch (\Exception $e) {
    //         \Log::error('Error updating story: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
    //     }
    // }

    public function update(Request $request, string $id)
    {
        try {
            $user = auth()->user();
    
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }
    
            // Validate the request data
            $validatedData = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'category_id' => 'sometimes|exists:categories,id',
                'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
    
            // Find the story or fail
            $story = Story::findOrFail($id);
    
            // Update the story fields
            if (isset($validatedData['title'])) {
                $story->title = $validatedData['title'];
            }
            if (isset($validatedData['content'])) {
                $story->content = $validatedData['content'];
            }
            if (isset($validatedData['category_id'])) {
                $story->category_id = $validatedData['category_id'];
            }

            $story->save();
    
            // Delete all existing images
            foreach ($story->images as $image) {
                Storage::delete('public/images/' . basename($image->filename));
                $image->delete();
            }
    
            // Handle new image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $filename = time() . '_' . $image->getClientOriginalName();
                    $image->storeAs('public/images', $filename);
                    
                    // Store full path in database
                    $fullPath = Storage::url('public/images/' . $filename);
                    $story->images()->create([
                        'filename' => $fullPath
                    ]);
                }
            }
    
            return response()->json([
                'message' => 'Story updated successfully',
                'story' => $story->load('images'),
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error updating story: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }
      
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = auth()->user();
    
            // Find the story by ID
            $story = Story::with('images')->find($id); // Ensure images relationship is loaded
            if (!$story) {
                return response()->json([
                    'message' => 'Story not found'
                ], 404);
            }
    
            // Check if the story belongs to the authenticated user
            if ($story->user_id !== $user->id) {
                return response()->json([
                    'message' => 'You are not authorized to delete this story'
                ], 403);
            }
    
            // Delete associated images
            if ($story->images && count($story->images) > 0) {
                foreach ($story->images as $image) {
                    $imagePath = public_path($image->filename); // Get full path of the image
                    if (file_exists($imagePath)) {
                        unlink($imagePath); // Delete the file
                        Log::info("Deleted file: {$imagePath}");
                    } else {
                        Log::warning("File not found: {$imagePath}");
                    }
                    $image->delete(); // Delete the image record
                }
            }
    
            // Delete the story
            $story->delete();
    
            return response()->json([
                'message' => 'Story and associated images deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error deleting story: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function myStories()
    {
        try {
            $user = auth()->user();

            if(!$user){
                return response()->json([
                    'message' => 'User not authenticated'
                ], 401);
            }

            $stories = Story::with('images')
                ->where('user_id', $user->id)
                ->get();

            $stories->each(function ($story) {
                $story->images->each(function ($image) {
                    $image->url = asset('storage/' . $image->filename);
                    return $image;
                });
            });

            return response()->json([
                'status' => true,
                'data' => [
                    'stories' => $stories,
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve stories',
                'error' => $th->getMessage()
            ]);
        }
    }
    
}
