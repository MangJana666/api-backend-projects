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
        $user = auth()->user();

        if(!$user){
            return response()->json([
                'message' =>  'User not found'
            ], 404);
        }else{
            return response()->json([
                'message' => 'User found',
                'user' => [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar
                ]
            ], 200);
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
        $user = auth()->user();
        $user = Users::find($id);

        if(!$user){
            return response()->json([
                'message' =>  'User not found'
            ], 404);
        }else{
            return response()->json([
                'message' => 'User found',
                'user' => [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar
                ]
            ], 200);
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:50',
            'username' => 'required|max:30|unique:users,username',
            'email' => 'required|unique:users,email',
            'password' => [
                'required',
                'min:8',
                'max:30',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,30}$/'
            ]
        ], [
            'name.required' => 'Name is required',
            'username.required' => 'Username is required',
            'username.unique' => 'Username already exists',
            'email.required' => 'Email is required',
            'email.unique' => 'Email already exists',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password does not match',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one digit, without any special characters.'
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => "Validation error", 'errors' => $validator->errors()
            ]);
        }

        $user = Users::create([
            'name' => $request->input('name'),
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        if(!$user){
            return response()->json([
                'message' => 'Failed to create user'
            ], 400);
        }else{
            return response()->json([
                'message' => 'User created successfully',
                'data' => [
                    "token" => $token],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'about' => $user->about
                ]
            ], 200);
        }
    }

    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'login_identifier' => 'required',
            'password' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'data' => [
                    "errors" => $validator->invalid()
                ]
            ], 422);
        }

        $user = Users::where('email', $request->input('login_identifier'))
            ->orWhere('username', $request->input('login_identifier'))
            ->first();

            if(!$user || !Hash::check($request->password, $user->password)){
                throw ValidationException::withMessages([
                    'login_identifier' => ['The provided credentials are incorrect.']
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                "data" => [
                    "token" => $token,
                    "user" => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'about' => $user->about
                    ]
                ]
        ], 200);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'User Logged out successfully'], 200);
    }

    public function updatePassword(Request $request){

        $user = auth()->user();
        $validateData = $request->validate([
            'old_password' => 'required_with:new_password',
            'new_password' => 'nullable|min:8|max:30|confirmed',
        ], [
            'old_password.required_with' => 'Old password is required',
            'new_password.confirmed' => 'New password does not match'
        ]);

        if(!Hash::check($request->old_password, $user->password)){
            return response()->json([
                'message' => 'Old password is incorrect'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully'
        ], 200);
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

    public function updateUserProfile(Request $request) {
        try {
            $user = auth()->user();
    
            $validateData = $request->validate([
                'name' => 'required|max:50',
                'avatar' => 'required',
                'about' => 'required',
            ]);
    
            $user->name = $validateData['name'];
            $user->avatar = $validateData['avatar'];
            $user->about = $validateData['about'];
    
            $user->save();
    
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
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false], 500);
        }
    }
    

    public function uploadAvatar(Request $request)
    {
        try {
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
                'status' => true,
                'data' => ['url' => $baseUrl]
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => false
            ], 500);
        }
    }
}
