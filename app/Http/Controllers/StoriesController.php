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
    public function allStories()
    {
        try {
            $keyword = request()->input('keyword', '');
            
            $query = Story::with(['images', 'category', 'user']);
    
            if (!empty($keyword)) {
                $query->where('title', 'like', '%' . $keyword . '%');
            }
    
            $stories = $query->paginate(12);
    
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
                    'created_at' => $story->created_at->format('d F Y'),
                    'category' => [
                        'name' => $story->category->name
                    ],
                    'user' => [
                        'id' => $story->user->id,
                        'avatar' => $story->user->avatar,
                        'username' => $story->user->username
                    ],
                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset($image->filename)
                        ];
                    })
                ];
            });
    
            return response()->json([
                'data' => [
                    'stories' => $formattedStories,
                    'pagination' => [
                        'total' => $stories->total(),
                        'per_page' => $stories->perPage(),
                        'current_page' => $stories->currentPage(),
                        'last_page' => $stories->lastPage(),
                        'next_page_url' => $stories->nextPageUrl(),
                        'prev_page_url' => $stories->previousPageUrl()
                    ]
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
                ->orderBy('created_at', 'desc')
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
                    'created_at' => $story->created_at->format('d F Y'),
                    'category' => [
                        'name' => $story->category->name
                    ],
                    'user' => [
                        'id' => $story->user->id,
                        'avatar' => $story->user->avatar,
                        'username' => $story->user->username
                    ],
                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset( $image->filename)
                        ];
                    })
                ];
            });
    
            return response()->json([
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
            $user = auth()->user();
            $story = Story::with(['images', 'user', 'category'])->find($id);
    
            if (!$story) {
                return response()->json([
                    'message' => 'Story not found'
                ], 404);
            }
    
            // $story->images->transform(function ($image) {
            //     $image->url = asset($image->filename);
            //     return $image;
            // });

            $simmilarStories = Story::with(['images', 'user', 'category'])
                ->where('category_id', $story->category_id)
                ->where('id', '!=', $story->id)
                ->paginate(3);

            $formattedStories = [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'created_at' => $story->created_at->format('d F Y'),

                    'user' => [
                        'id' => $story->user->id,
                        'avatar' => $story->user->avatar,
                        'username' => $story->user->username
                    ],

                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset($image->filename)
                        ];
                    })
                ];

            $formattedSimmilarStories = $simmilarStories->map(function($story){
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'created_at' => $story->created_at->format('d F Y'),

                    'user' => [
                        'id' => $story->user->id,
                        'avatar' => $story->user->avatar,
                        'username' => $story->user->username
                    ],

                    'category' => [
                        'id' => $story->category->id,
                        'name' => $story->category->name
                    ],

                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset($image->filename)
                        ];
                    })
                ];
            });
    
            return response()->json([
                'data' => [
                    'stories' => $formattedStories,
                    'simmilarStories' => $formattedSimmilarStories
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();
    
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
    
            $story = Story::create([
                'title' => $validateData['title'],
                'content' => $validateData['content'],
                'category_id' => $validateData['category_id'],
                'user_id' => $user->id,
            ]);
    
            if ($request->hasFile('images')) {
                $uploadedImages = $request->file('images');
    
                if (count($uploadedImages) > 5) {
                    return response()->json([
                        'message' => 'You can only upload a maximum of 5 images.',
                    ], 422);
                }
    
                foreach ($uploadedImages as $index => $image) {
                    $imageName = time() . "_{$index}_" . md5(uniqid(rand(), true)) . '.' . $image->extension();
                    $imagePath = $image->storeAs('images', $imageName, 'public');
                    $story->images()->create(['filename' => "/storage/{$imagePath}"]);
                }
            }
    
            $story->load('images');
    
            return response()->json([
                'message' => 'Story created successfully',
                'story' => [
                    'data' => $story,
                    'images' => $story->images->map(function ($image) {
                        return $image->filename;
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

    public function update(Request $request, string $id)
    {
        try {
            $user = auth()->user();
    
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }
    
            $validatedData = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'category_id' => 'sometimes|exists:categories,id',
                'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
    
            $story = Story::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
    
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
    
            foreach ($story->images as $image) {
                Storage::delete('public/images/' . basename($image->filename));
                $image->delete();
            }
    
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $filename = time() . '_' . $image->getClientOriginalName();
                    $image->storeAs('public/images', $filename);
                    
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

    // public function update(Request $request, string $id)
    // {
    //     try {
    //         $user = auth()->user();
    
    //         if (!$user) {
    //             return response()->json(['message' => 'User not authenticated'], 401);
    //         }
    
    //         $story = Story::where('id', $id)
    //             ->where('user_id', $user->id)
    //             ->first();
    
    //         if (!$story) {
    //             return response()->json(['message' => 'Story not found'], 404);
    //         }
    
    //         $validatedData = $request->validate([
    //             'title' => 'sometimes|string|max:255',
    //             'content' => 'sometimes|string',
    //             'category_id' => 'sometimes|exists:categories,id',
    //             'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //             'delete_image_ids' => 'sometimes|array',
    //             'delete_image_ids.*' => 'exists:images,id'
    //         ]);
    
    //         // Calculate final image count
    //         $currentImagesCount = $story->images()->count();
    //         $deleteImagesCount = $request->has('delete_image_ids') ? count($request->delete_image_ids) : 0;
    //         $newImagesCount = $request->hasFile('images') ? count($request->file('images')) : 0;
    //         $finalImageCount = $currentImagesCount - $deleteImagesCount + $newImagesCount;
    
    //         if ($finalImageCount > 5) {
    //             return response()->json([
    //                 'message' => 'Maximum 5 images allowed per story. Current total would be ' . $finalImageCount
    //             ], 422);
    //         }
    
    //         // Update story data
    //         $story->fill($request->only(['title', 'content', 'category_id']));
    //         $story->save();
    
    //         // Delete specific images if requested
    //         if ($request->has('delete_image_ids')) {
    //             foreach ($request->delete_image_ids as $imageId) {
    //                 $image = $story->images()->find($imageId);
    //                 if ($image) {
    //                     Storage::delete('public/images/' . basename($image->filename));
    //                     $image->delete();
    //                 }
    //             }
    //         }
    
    //         // Add new images if provided
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $image) {
    //                 $filename = time() . '_' . $image->getClientOriginalName();
    //                 $image->storeAs('public/images', $filename);
                    
    //                 $fullPath = Storage::url('public/images/' . $filename);
    //                 $story->images()->create([
    //                     'filename' => $fullPath
    //                 ]);
    //             }
    //         }
    
    //         return response()->json([
    //             'message' => 'Story updated successfully',
    //             'story' => $story->fresh(['images']),
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         \Log::error('Error updating story: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json([
    //             'message' => 'Something went wrong', 
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
    
    //         $story = Story::where('id', $id)
    //             ->where('user_id', $user->id)
    //             ->with(['images' => function($query) {
    //                 $query->orderBy('created_at', 'asc');
    //             }])
    //             ->first();
    
    //         if (!$story) {
    //             return response()->json(['message' => 'Story not found'], 404);
    //         }
    
    //         $validatedData = $request->validate([
    //             'title' => 'sometimes|string|max:255',
    //             'content' => 'sometimes|string',
    //             'category_id' => 'sometimes|exists:categories,id',
    //             'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //             'delete_image_ids' => 'sometimes|array',
    //             'delete_image_ids.*' => [
    //                 'required',
    //                 'integer',
    //                 Rule::exists('images', 'id')->where(function ($query) use ($story) {
    //                     $query->where('story_id', $story->id);
    //                 }),
    //             ],
    //         ]);
    
    //         $story->fill($request->only(['title', 'content', 'category_id']));
    //         $story->save();
    
    //         if ($request->has('delete_image_ids')) {
    //             foreach ($request->delete_image_ids as $imageId) {
    //                 $image = $story->images()->find($imageId);
    //                 if ($image) {
    //                     Storage::delete('public/images/' . basename($image->filename));
    //                     $image->delete();
    //                 }
    //             }
    //         }
    
    //         // Upload new images if any
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $image) {
    //                 $filename = time() . '_' . $image->getClientOriginalName();
    //                 $image->storeAs('public/images', $filename);
                    
    //                 $story->images()->create([
    //                     'filename' => Storage::url('public/images/' . $filename)
    //                 ]);
    //             }
    //         }
    
    //         // Reload and format remaining images
    //         $story = $story->fresh(['images']);
    //         $formattedImages = $story->images->map(function($image, $index) {
    //             return [
    //                 'id' => $image->id,
    //                 'filename' => $image->filename,
    //                 'url' => asset($image->filename),
    //                 'position' => $index + 1
    //             ];
    //         });
    
    //         return response()->json([
    //             'message' => 'Story updated successfully',
    //             'story' => [
    //                 'id' => $story->id,
    //                 'title' => $story->title,
    //                 'content' => $story->content,
    //                 'category_id' => $story->category_id,
    //                 'images' => $formattedImages
    //             ]
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         \Log::error('Error updating story: ' . $e->getMessage());
    //         return response()->json([
    //             'message' => 'Something went wrong',
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
    
    //         $story = Story::where('id', $id)
    //             ->where('user_id', $user->id)
    //             ->with(['images' => function($query) {
    //                 $query->orderBy('created_at', 'asc');
    //             }])
    //             ->first();
    
    //         if (!$story) {
    //             return response()->json(['message' => 'Story not found'], 404);
    //         }
    
    //         $validatedData = $request->validate([
    //             'title' => 'sometimes|string|max:255',
    //             'content' => 'sometimes|string',
    //             'category_id' => 'sometimes|exists:categories,id',
    //             'cover_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //         ]);
    
    //         $story->fill($request->only(['title', 'content', 'category_id']));
    //         $story->save();
    
    //         if ($request->hasFile('cover_image')) {
    //             $coverImage = $story->images->first();
                
    //             if ($coverImage) {
    //                 Storage::delete('public/images/' . basename($coverImage->filename));
    //                 $newCover = $request->file('cover_image');
    //                 $filename = time() . '_' . $newCover->getClientOriginalName();
    //                 $newCover->storeAs('public/images', $filename);
    //                 $coverImage->update([
    //                     'filename' => Storage::url('public/images/' . $filename)
    //                 ]);
    //             }
    //         }
    
    //         $story = $story->fresh(['images']);
            
    //         return response()->json([
    //             'message' => 'Story updated successfully',
    //             'story' => [
    //                 'id' => $story->id,
    //                 'title' => $story->title,
    //                 'content' => $story->content,
    //                 'category_id' => $story->category_id,
    //                 'images' => $story->images->map(function($image) {
    //                     return [
    //                         'id' => $image->id,
    //                         'filename' => $image->filename,
    //                         'url' => asset($image->filename)
    //                     ];
    //                 })
    //             ]
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         \Log::error('Error updating story: ' . $e->getMessage());
    //         return response()->json([
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
      
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = auth()->user();
    
            $story = Story::with('images')->find($id);
            if (!$story) {
                return response()->json([
                    'message' => 'Story not found'
                ], 404);
            }
    
            if ($story->user_id !== $user->id) {
                return response()->json([
                    'message' => 'You are not authorized to delete this story'
                ], 403);
            }
    
            if ($story->images && count($story->images) > 0) {
                foreach ($story->images as $image) {
                    $imagePath = public_path($image->filename);
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                        Log::info("Deleted file: {$imagePath}");
                    } else {
                        Log::warning("File not found: {$imagePath}");
                    }
                    $image->delete();
                }
            }
    
            $story->delete();
    
            return response()->json([
                'message' => 'Story and associated images deleted successfully'
            ], 200);
        } catch (\Exception $e) {
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
    
            $stories = Story::with(['images', 'category', 'user'])
                ->where('user_id', $user->id)
                ->paginate(4);

    
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
                    'created_at' => $story->created_at->format('d F Y'),
                    'category' => [
                        'id' => $story->category->id,
                        'name' => $story->category->name
                    ],
                    'user' => [
                        'id' => $story->user->id,
                        'username' => $story->user->username
                    ],
                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset($image->filename)
                        ];
                    })
                ];
            });
    
            return response()->json([
                'data' => [
                    'stories' => $formattedStories,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name
                    ],
                    'pagination' => [
                        'total' =>$stories->total(),
                        'per_page' => $stories->perPage(),
                        'current_page' => $stories->currentPage(),
                        'last_page' => $stories->lastPage(),
                        'next_page_url' => $stories->nextPageUrl(),
                        'prev_page_url' => $stories->previousPageUrl()
                    ]
                ]
            ], 200);
                
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to retrieve stories',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function getNewestStory()
    {
        try {
            $stories = Story::with(['images', 'category', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(12);
    
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
                    'created_at' => $story->created_at->format('d F Y'),
                    'category' => [
                        'name' => $story->category->name
                    ],
                    'user' => [
                        'id' => $story->user->id,
                        'avatar' => $story->user->avatar,
                        'username' => $story->user->username
                    ],
                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset($image->filename)
                        ];
                    })
                ];
            });
    
            return response()->json([
                'data' => [
                    'stories' => $formattedStories,
                    'pagination' => [
                        'total' => $stories->total(),
                        'per_page' => $stories->perPage(),
                        'current_page' => $stories->currentPage(),
                        'last_page' => $stories->lastPage(),
                        'next_page_url' => $stories->nextPageUrl(),
                        'prev_page_url' => $stories->previousPageUrl()
                    ]
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve stories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sortStory(Request $request)
    {
        try {
            $sort = $request->query('sort', 'asc', 'desc');

            $query = Story::with(['images', 'category', 'user'])
                ->orderBy('title', strtolower($sort));

            $stories = $query->paginate(12);

            if($stories->isEmpty()){
                return response()->json([
                    'message' => 'No stories found'
                ], 404);
            }

            $formattedStories = $stories->map(function($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'created_at' => $story->created_at->format('d F Y'),
                    'category' => [
                        'name' => $story->category->name,
                    ],

                    'user' => [
                        'id' => $story->user->id,
                        'avatar' => $story->user->avatar,
                        'username' => $story->user->username,
                    ],

                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset($image->filename),
                        ];
                    }),
                ];
            });

            return response()->json([
                'data' => [
                    'stories' => $formattedStories,
                    'pagination' => [
                        'total' => $stories->total(),
                        'per_page' => $stories->perPage(),
                        'current_page' => $stories->currentPage(),
                        'last_page' => $stories->lastPage(),
                        'next_page_url' => $stories->nextPageUrl(),
                        'prev_page_url' => $stories->previousPageUrl(),
                    ]
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to retrieve stories',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function newestStoryIndex()
    {
        try {
            $stories = Story::with(['images', 'category', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(6);
    
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
                    'created_at' => $story->created_at->format('d F Y'),
                    'category' => [
                        'name' => $story->category->name
                    ],
                    'user' => [
                        'id' => $story->user->id,
                        'avatar' => $story->user->avatar,
                        'username' => $story->user->username
                    ],
                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset($image->filename)
                        ];
                    })
                ];
            });
    
            return response()->json([
                'data' => [
                    'stories' => $formattedStories,
                    'pagination' => [
                        'total' => $stories->total(),
                        'per_page' => $stories->perPage(),
                        'current_page' => $stories->currentPage(),
                        'last_page' => $stories->lastPage(),
                        'next_page_url' => $stories->nextPageUrl(),
                        'prev_page_url' => $stories->previousPageUrl()
                    ]
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve stories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPopularStory()
    {
        try {
            $stories = Story::withCount('bookmarks')
                ->orderBy('bookmarks_count', 'desc')
                ->paginate(12);

            if ($stories->isEmpty()){
                return response()->json([
                    'message' => 'No stories found'
                ], 404);
            }

            $formattedStories = $stories->map(function($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'created_at' => $story->created_at->format('d F Y'),
                    'bookmarks_count' => $story->bookmarks_count,
                    'category' => [
                        'name' => $story->category->name
                    ],
                    'user' => [
                        'id' => $story->user->id,
                        'avatar' => $story->user->avatar,
                        'username' => $story->user->username
                    ],
                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset($image->filename)
                        ];
                    })
                ];
            });

            return response()->json([
                'data' => [
                    'stories' => $formattedStories,
                    'pagination' => [
                        'total' => $stories->total(),
                        'per_page' => $stories->perPage(),
                        'current_page' => $stories->currentPage(),
                        'last_page' => $stories->lastPage(),
                        'next_page_url' => $stories->nextPageUrl(),
                        'prev_page_url' => $stories->previousPageUrl()
                    ]
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to retrieve popular stories',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
