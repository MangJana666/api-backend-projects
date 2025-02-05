<?php

namespace App\Traits;

trait StoryResponse
{
    public function successResponse($data = null, $message = 'action success', $code = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ]);
    }

    //Return Response
    protected function mappingFunctionIn($stories)
    {
        return $stories->map(function($story) {
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
    }

    protected function paginationResponse($paginator)
    {
        return [
            'total' => $paginator->total(),
            'result_data' => $paginator->count(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'first_page_url' => $paginator->url(1),
            'last_page_url' => $paginator->url($paginator->lastPage())
        ];
    }

    protected function formatStoryResponse($stories)
    {
        return $this->successResponse([
            'stories' => $this->mappingFunctionIn($stories),
            'pagination' => $this->paginationResponse($stories)
        ]);
    }
}
