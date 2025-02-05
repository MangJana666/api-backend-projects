<?php

namespace App\Http\Controllers;

use Log;
use Exception;
use Throwable;
use App\Models\Story;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Traits\StoryResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StoriesController extends Controller
{
    use ApiResponse;
    use StoryResponse;

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
                return $this->notFoundResponseStory();
            }
    
            return $this->formatStoryResponse($stories);
    
        } catch (\Exception $e) {
            return $this->internalServerError();
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
                $this->notFoundResponseStory();
            }
    
            return response()->json([
                'data' => [
                    'stories' => $this->mappingFunctionIn($stories)
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return $this->internalServerError();
        }
    }


    public function show($id)
    {
        try {
            $user = auth()->user();
            
            $story = Story::with(['images', 'user', 'category'])->find($id);
    
            if (!$story) {
                $this->notFoundResponseStory();
            }

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
            return $this->internalServerError();
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            $this->authorize('create', Story::class);
    
            $validateData = $request->validate([
                'title' => 'required',
                'content' => 'required',
                'images' => 'array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'category_id' => 'required|exists:categories,id',
            ]);
    
            if (!$user) {
                return $this->unauthorizedResponse();
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
            return $this->internalServerError();
        }
    }

    // public function update(Request $request, string $id)
    // {
    //     try {

    //         $user = auth()->user();

    //         if (!$user) {
    //             return $this->unauthorizedResponse();
    //         }
    
    //         $story = Story::where('id', $id)->first();
    //         if (!$story) {
    //             return $this->notFoundResponseStory();
    //         }

    //         $this->authorize('update', $story);
            
    //         $validatedData = $request->validate([
    //             'title' => 'sometimes|string|max:255',
    //             'content' => 'sometimes|string',
    //             'category_id' => 'sometimes|exists:categories,id',
    //             'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    //         ]);
    
    //         $story = Story::where('id', $id)
    //             ->where('user_id', $user->id)
    //             ->first();
    
    //         if (isset($validatedData['title'])) {
    //             $story->title = $validatedData['title'];
    //         }
    //         if (isset($validatedData['content'])) {
    //             $story->content = $validatedData['content'];
    //         }
    //         if (isset($validatedData['category_id'])) {
    //             $story->category_id = $validatedData['category_id'];
    //         }

    //         $story->save();
    
    //         foreach ($story->images as $image) {
    //             Storage::delete('public/images/' . basename($image->filename));
    //             $image->delete();
    //         }
    
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
    //             'story' => $story->load('images'),
    //         ], 200);
    //     } catch (\Exception $e) {
    //         \Log::error('Error updating story: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return $this->internalServerError();
    //     }
    // }

    public function update(Request $request, string $id)
    {
        try {
            $user = auth()->user();
    
            if (!$user) {
                return $this->unauthorizedResponse();
            }
    
            $story = Story::where('id', $id)->first();
            if (!$story) {
                return $this->notFoundResponseStory();
            }
    
            $this->authorize('update', $story);
            
            $validatedData = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'category_id' => 'sometimes|exists:categories,id',
                'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'delete_images' => 'sometimes|array',
                'delete_images*' => 'exists:images,id'
            ]);
    
            $story->fill($request->only(['title', 'content', 'category_id']));
            $story->save();
    
            if ($request->has('delete_images')) {
                foreach ($request->delete_images as $imageId) {
                    $image = $story->images()->find($imageId);
                    if ($image) {
                        Storage::delete('public/images/' . basename($image->filename));
                        $image->delete();
                    }
                }
            }
    
            if ($request->hasFile('images')) {
                $currentImagesCount = $story->images()->count();
                $newImagesCount = count($request->file('images'));
    
                if (($currentImagesCount + $newImagesCount) > 5) {
                    return response()->json([
                        'message' => 'Maximum 5 images allowed per story'
                    ], 422);
                }
    
                foreach ($request->file('images') as $image) {
                    $filename = time() . '_' . $image->getClientOriginalName();
                    $path = $image->storeAs('images', $filename, 'public');
                    $story->images()->create([
                        'filename' => "/storage/{$path}"
                    ]);
                }
            }
    
            return response()->json([
                'message' => 'Story updated successfully',
                'story' => $story->fresh(['images']),
            ], 200);
    
        } catch (\Exception $e) {
            \Log::error('Error updating story: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->internalServerError();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $user = auth()->user();
    
            $story = Story::with('images')->find($id);
            if (!$story) {
                $this->notFoundResponseStory();
            }

            $this->authorize('delete', $story);
    
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
    
            return $this->internalServerError();
        }
    }

    public function myStories()
    {
        try {
            $user = auth()->user();
    
            $this->authorize('viewMyStories', Story::class);
    
            $stories = Story::with(['images', 'category', 'user'])
                ->where('user_id', $user->id)
                ->paginate(4);
    
            if ($stories->isEmpty()) {
                return $this->notFoundResponseStory();
            }
    
            return $this->formatStoryResponse($stories);
                
        } catch (\Exception $e) {
            \Log::error('Error fetching stories: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->internalServerError();
        }
    }

    public function getNewestStory()
    {
        try {
            $stories = Story::with(['images', 'category', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(12);
    
            if ($stories->isEmpty()) {
                $this->notFoundResponseStory();
            }
    
            return $this->formatStoryResponse($stories);
    
        } catch (\Exception $e) {
            return $this->internalServerError();
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
                $this->notFoundResponseStory();
            }

            return $this->formatStoryResponse($stories);
        } catch (\Throwable $th) {
            return $this->internalServerError();
        }
    }

    public function newestStoryIndex()
    {
        try {
            $stories = Story::with(['images', 'category', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(6);
    
            if ($stories->isEmpty()) {
                $this->notFoundResponseStory();
            }

            return $this->formatStoryResponse($stories);
    
        } catch (\Exception $e) {
            return $this->internalServerError();
        }
    }

    public function getPopularStory()
    {
        try {
            $stories = Story::withCount('bookmarks')
                ->orderBy('bookmarks_count', 'desc')
                ->paginate(12);

            if ($stories->isEmpty()){
                $this->notFoundResponseStory();
            }

            return response()->json([
                'data' => [
                    'stories' => $this->mappingFunctionIn($stories),
                    'pagination' => $this->paginationResponse($stories)
                ]
            ], 200);
        } catch (\Throwable $th) {
            return $this->internalServerError();
        }
    }
}
