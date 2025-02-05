<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Story;
use App\Models\Bookmark;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookmarksController extends Controller
{
    use ApiResponse;
    public function getUserBookmarks(){
        try {
            $user = auth()->user();

            if(!$user){
                $this->unauthorizedResponse();
            }

            $this->authorize('viewAny', Bookmark::class);

            $bookmarkedStory = Story::whereHas('bookmarks', function($query) use ($user){
                $query->where('user_id', $user->id);
            })
            ->with(['images', 'category', 'user'])
            ->paginate(4);

            if ($bookmarkedStory->isEmpty()) {
                return response()->json([
                    'message' => 'No bookmarked story found',
                    'data' => []
                ], 200);
            }

            $formattedStories = $bookmarkedStory->map(function($story) {
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
                        'username' => $story->user->username,
                        'avatar' => $story->user->avatar
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
                        'total' => $bookmarkedStory->total(),
                        'per_page' => $bookmarkedStory->perPage(),
                        'current_page' => $bookmarkedStory->currentPage(),
                        'last_page' => $bookmarkedStory->lastPage(),
                        'next_page_url' => $bookmarkedStory->nextPageUrl(),
                        'prev_page_url' => $bookmarkedStory->previousPageUrl()
                    ]
                ]
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Error getting user bookmarks: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);
    
            return response()->json([
                'message' => 'Failed to retrieve bookmarks',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function addStoryToBookmarks(Request $request, Story $story)
    {
        try {
            $user = auth()->user();
    
            if (!$user) {
                return $this->unauthorizedResponse();
            }
    
            $this->authorize('create', $story);
            
            $bookmark = Bookmark::where('user_id', $user->id)
                ->where('story_id', $story->id)
                ->first();
    
            $status = true;
            
            if ($bookmark) {
                $bookmark->delete();
                $message = 'Bookmark removed successfully';
            } else {
                Bookmark::create([
                    'user_id' => $user->id,
                    'story_id' => $story->id
                ]);
                $message = 'Story bookmarked successfully';
            }
    
            return response()->json([
                'status' => $status,
                'message' => $message,
                'bookmarks_count' => $story->fresh()->bookmarks_count
            ], 200);
    
        } catch (\Exception $e) {
            Log::error('Error toggling bookmark: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'status' => false,
                'message' => 'Failed to toggle bookmark',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
