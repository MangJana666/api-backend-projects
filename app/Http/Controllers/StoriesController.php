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
                            'url' => asset('storage/' . $image->filename)
                        ];
                    })
                ];
            });
    
            return response()->json([
                'status' => true,
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
                'status' => true,
                'data' => [
                    'stories' => $formattedStories,
                    'simmilarStories' => $formattedSimmilarStories
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
                'status' => true,
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
                'status' => false,
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
                'status' => true,
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
                'status' => false,
                'message' => 'Failed to retrieve stories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sortStory(Request $request)
    {
        try {
            $sort = $request->query('sort', 'asc');

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
                        'username' => $story->user->username,
                    ],

                    'images' => $story->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'url' => asset('storage/' . $image->filename),
                        ];
                    }),
                ];
            });

            return response()->json([
                'status' => true,
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
                'status' => false,
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
                'status' => true,
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
                'status' => false,
                'message' => 'Failed to retrieve stories',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
