<?php

namespace App\Traits;

trait BookmarkResponse
{
    // protected function successResponseBookmark($data = null, $message = 'Action Bookmark Success')
    // {
    //     return response()->json([
    //         'message' => $message,
    //         'data' => $data
    //     ]);
    // }

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
