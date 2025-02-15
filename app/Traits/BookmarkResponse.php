<?php

namespace App\Traits;

trait BookmarkResponse
{
    protected function successResponseBookmark($data = null, $message = 'Action Bookmark Success', $code = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ]);
    }

    //Return Response
    protected function mappingFuntionBookmark($stories)
    {
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
    }

    protected function paginateBookmarks($paginatorBookmarks)
    {
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
        ], $this->successResponseBookmark($code));
    }

    // protected function bookmarkReturnData($bookmarkStory)
    // {
    //     return [
    //         'id' => $story->id,
    //         'title' => $story->title,
    //         'content' => $story->content,
    //         'created_at' => $story->created_at->format('d F Y'),
    //         'category' => [
    //             'name' => $story->category->name
    //         ],
    //         'user' => [
    //             'id' => $story->user->id,
    //             'username' => $story->user->username,
    //             'avatar' => $story->user->avatar
    //         ],
    //         'images' => $story->images->map(function($image) {
    //             return [
    //                 'id' => $image->id,
    //                 'filename' => $image->filename,
    //                 'url' => asset($image->filename)
    //             ];
    //         })
    //     ];
    // }
}
