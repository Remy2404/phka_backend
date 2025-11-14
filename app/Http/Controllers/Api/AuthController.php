<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use OpenApi\Annotations as OA;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Info(
 *     title="Phka Backend API Reference",
 *     version="1.0.0",
 *     description="Authentication and user profile endpoints for the Phka beauty commerce backend"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Local development server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your bearer token in the format 'Bearer {token}'"
 * )
 *
 * @OA\Tag(name="Auth", description="Authentication flows")
 */
class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Auth"},
     *     summary="Register a new customer",
     *     description="Creates a new user and returns a Sanctum token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Ramy Phon"),
     *             @OA\Property(property="email", type="string", format="email", example="ramy@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+14155552671"),
     *             @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-01-01"),
     *             @OA\Property(property="gender", type="string", nullable=true, enum={"male","female","other"}),
     *             @OA\Property(property="skin_type", type="string", nullable=true, enum={"oily","dry","combination","normal","sensitive"}),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="token", type="string", example="1|abc123")
     *             ),
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'skin_type' => $request->skin_type,
            'loyalty_points' => 0,
            'is_active' => true,
        ]);

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     summary="Login user",
     *     description="Authenticate user and return a Sanctum token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="ramy@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="device_name", type="string", nullable=true, example="iPhone 12")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="token", type="string", example="2|abc123")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     *
     * Login user and return token.
     */
    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken($request->device_name ?: 'API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Auth"},
     *     summary="Logout user",
     *     description="Revoke the current access token",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout successful")
     *         )
     *     )
     * )
     *
     * Logout user and revoke token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/profile",
     *     tags={"Auth"},
     *     summary="Get user profile",
     *     description="Returns authenticated user profile with addresses and recent orders",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     *
     * Get authenticated user profile.
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()->load(['addresses', 'orders' => function($query) {
                    $query->latest()->limit(5);
                }])
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/auth/profile",
     *     tags={"Auth"},
     *     summary="Update user profile",
     *     description="Update authenticated user's profile information",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890"),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="gender", type="string", enum={"male","female","other"}, example="male"),
     *                 @OA\Property(property="skin_type", type="string", enum={"oily","dry","combination","normal","sensitive"}, example="combination"),
     *                 @OA\Property(property="avatar", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     *
     * Update user profile.
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->only([
            'name', 'phone', 'birth_date', 'gender', 'skin_type'
        ]));

        // Handle avatar upload if provided
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $avatarPath]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/auth/change-password",
     *     tags={"Auth"},
     *     summary="Change user password",
     *     description="Change authenticated user's password and revoke all existing tokens",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "password", "password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="OldPassword123!"),
     *             @OA\Property(property="password", type="string", format="password", example="NewPassword123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewPassword123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password changed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="3|abc123")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     *
     * Change user password.
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Revoke all tokens to force re-login
        $user->tokens()->delete();
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
            'data' => [
                'token' => $token
            ]
        ]);
    }
}