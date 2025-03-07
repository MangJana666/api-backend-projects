<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'message' => 'User found',
                'user' => [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => false
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $token = auth()->user();

            $user = Users::find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'message' => 'User found',
                // 'data' => $token,
                'user' => [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'about' => $user->about
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateUserProfile(Request $request, string $id)
    {
        try {
            $user = auth()->user();

            $this->authorize('update', $user);

            $validateData = $request->validate([
                'name' => 'sometimes|max:50',
                'avatar' => 'sometimes',
                'about' => 'sometimes',
            ]);

            // $user->name = $validateData['name'];
            // $user->avatar = $validateData['avatar'];
            // $user->about = $validateData['about'];
            // $user->save();

            $user->update([
                'name' => $validateData['name'],
                'avatar' => $validateData['avatar'],
                'about' => $validateData['about']
            ]);

            return response()->json([
                'message' => 'User profile updated successfully',
                'user' => [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'about' => $user->about
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function updatePassword(Request $request)
    {
        try {
            $user = auth()->user();

            $this->authorize('updatePassword', $user);
            
            $validateData = $request->validate([
                'old_password' => 'required_with:new_password',
                'new_password' => 'nullable|min:8|max:30|confirmed',
            ], [
                'old_password.required_with' => 'Old password is required',
                'new_password.confirmed' => 'New password does not match'
            ]);

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'message' => 'Old password is incorrect'
                ], 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'message' => 'Password updated successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function updateUserProfile(Request $request){
    //     $user = auth()->user();

    //     $validateData = $request->validate([
    //         'name' => 'required|max:50',
    //         'avatar' => 'required',
    //         'about' => 'required',
    //     ], [
    //         'name.required' => 'Name is required',
    //         'avatar.required' => 'Avatar is required',
    //         'about.required' => 'About is required',
    //     ]);

    //     $user->name = $validateData['name'];
    //     $user->avatar = $validateData['avatar'];
    //     $user->about = $validateData['about'];

    //     $user->save();

    //     return response()->json([
    //         'message' => 'User profile updated successfully',
    //         'user' => [
    //             'name' => $user->name,
    //             'username' => $user->username,
    //             'email' => $user->email,
    //             'avatar' => $user->avatar,
    //             'about' => $user->about
    //         ]
    //     ], 200);
    // }

    // public function updateUserProfile(Request $request)
    // {
    //     try {
    //         $user = auth()->user();

    //         $validateData = $request->validate([
    //             'name' => 'required|max:50',
    //             'avatar' => 'required',
    //             'about' => 'required',
    //         ]);

    //         $user->name = $validateData['name'];
    //         $user->avatar = $validateData['avatar'];
    //         $user->about = $validateData['about'];

    //         $user->save();

    //         return response()->json([
    //             'message' => 'User profile updated successfully',
    //             'user' => [
    //                 'name' => $user->name,
    //                 'username' => $user->username,
    //                 'email' => $user->email,
    //                 'avatar' => $user->avatar,
    //                 'about' => $user->about
    //             ]
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'message' => $e->getMessage(),
    //             'status' => false
    //         ], 500);
    //     }
    // }
    

    public function uploadAvatar(Request $request)
    {
        try {
            $user = auth()->user();

            $this->authorize('uploadAvatar', $user);

            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
            $avatar = $request->file('avatar');
            if (!$avatar || !$avatar->isValid()) {
                return response()->json(['message' => 'Avatar is invalid', 'status' => false, 'data' => null], 422);
            }
            $avatarName = time();
            $resultAvatar = $avatar->storeAs('avatars', "{$avatarName}.{$avatar->extension()}", 'public');

            $user = auth()->user();
            $user->avatar = $resultAvatar;
            $user->save();
    
            if (!$resultAvatar) {
                return response()->json(['message' => 'Failed to store file', 'status' => false], 500);
            }
            $baseUrl = asset("storage/{$resultAvatar}");
    
            return response()->json([
                'message' => 'Upload File Success',
                'data' => ['url' => $baseUrl]
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
