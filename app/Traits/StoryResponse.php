<?php

namespace App\Traits;

trait StoryResponse
{
    //Success Response
    public function successResponse($data = null, $message = 'Action Story Success', $code = 200)
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

    protected function storyValidationRules($stories)
    {
        $validateData = $request->validate([
            'title' => 'required',
            'content' => 'required',
            'images' => 'array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'required|exists:categories,id',
        ]);
    }

    protected function formatSingleStory($story)
    {
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
    }

    protected function formatSimilarStories($stories)
    {
        return $stories->map(function($story) {
            return $this->formatSingleStory($story);
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
