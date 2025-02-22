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

    //All Story
    public function allStory()
    {
        try {
            $keyword = request()->input('keyword', '');
            
            $query = Story::with(['images', 'category', 'user']);
    
            if (!empty($keyword)) {
                $query->where('title', 'like', '%' . $keyword . '%');
            }
    
            $story = $query->paginate(12);
    
            if ($story->isEmpty()) {
                return $this->notFoundResponseStory();
            }
    
            return $this->formatStoryResponse($story);
    
        } catch (\Exception $e) {
            return $this->internalServerError();
        }
    }

    //Stories By Category
    public function storiesByCategory($categoryId)
    {
        try {
            $story = Story::with(['images', 'category', 'user'])
                ->where('category_id', $categoryId)
                ->orderBy('created_at', 'desc')
                ->get();
    
            if ($story->isEmpty()) {
                $this->notFoundResponseStory();
            }
    
            return response()->json([
                'data' => [
                    'stories' => $this->mappingFunctionIn($story)
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return $this->internalServerError();
        }
    }

    //Show
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
    
            return response()->json([
                'data' => [
                    'story' => $this->formatSingleStory($story),
                    'simmilarStories' => $this->formatSimilarStories($simmilarStories)
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return $this->internalServerError();
        }
    }

    //Create
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
                'data' => [
                    'story' => $story,
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

    //Update
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

    //Delete
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

    //User Story
    public function myStories()
    {
        try {
            $user = auth()->user();
    
            $this->authorize('viewMyStories', Story::class);
    
            $story = Story::with(['images', 'category', 'user'])
                ->where('user_id', $user->id)
                ->paginate(4);
    
            if ($story->isEmpty()) {
                return $this->notFoundResponseStory();
            }
    
            return $this->formatStoryResponse($story);
                
        } catch (\Exception $e) {
            \Log::error('Error fetching stories: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->internalServerError();
        }
    }

    //Latest / Newest Story
    public function newestStory()
    {
        try {
            $story = Story::with(['images', 'category', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(12);
    
            if ($story->isEmpty()) {
                $this->notFoundResponseStory();
            }
    
            return $this->formatStoryResponse($story);
    
        } catch (\Exception $e) {
            return $this->internalServerError();
        }
    }

    //Sort Story
    public function sortStory(Request $request)
    {
        try {
            $sort = $request->query('sort', 'asc', 'desc');

            $query = Story::with(['images', 'category', 'user'])
                ->orderBy('title', strtolower($sort));

            $story = $query->paginate(12);

            if($story->isEmpty()){
                $this->notFoundResponseStory();
            }

            return $this->formatStoryResponse($story);
        } catch (\Throwable $th) {
            return $this->internalServerError();
        }
    }

    //Newest Story Index
    public function latestStory()
    {
        try {
            $story = Story::with(['images', 'category', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(6);
    
            if ($story->isEmpty()) {
                $this->notFoundResponseStory();
            }

            return $this->formatStoryResponse($story);
    
        } catch (\Exception $e) {
            return $this->internalServerError();
        }
    }

    //Popular Story
    public function getPopularStory()
    {
        try {
            $story = Story::withCount('bookmarks')
                ->orderBy('bookmarks_count', 'desc')
                ->paginate(12);

            if ($story->isEmpty()){
                $this->notFoundResponseStory();
            }

            return response()->json([
                'data' => [
                    'stories' => $this->mappingFunctionIn($story),
                    'pagination' => $this->paginationResponse($story)
                ]
            ], 200);
        } catch (\Throwable $th) {
            return $this->internalServerError();
        }
    }

    //Filter Data by Categories
    public function getFilteredStory(Request $request, $filter = null)
    {
        try {
            $query = Story::with(['images', 'category', 'user']);

            $filter = $request->query('filter');
            $search = $request->query('search');

            if ($search){
                $query->where(function($q) use ($search){
                    $q->where('title', 'like', "%$search%");
                });
            }

            switch ($filter) {
                case 'popular':
                    $query->withCount('bookmarks')
                        ->orderBy('bookmarks_count', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'asc':
                    $query->orderBy('title', 'asc');
                    break;
                case 'desc':
                    $query->orderBy('title', 'desc');
                    break;
                default:
                    $query->latest();
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
    
            $stories = $query->paginate(12);
    
            if ($stories->isEmpty()) {
                return $this->notFoundResponseStory();
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
