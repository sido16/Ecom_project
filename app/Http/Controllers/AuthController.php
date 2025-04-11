<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Log; 


  /**
 * @OA\Info(
 *      title="Your API Documentation",
 *      version="1.0.0",
 *      description="API documentation for your Laravel project"
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="API Server"
 * )
 */

class AuthController extends Controller
{
   /**
 * @OA\Post(
 *     path="/api/register",
 *     summary="Register a new user",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"full_name","email","phone_number","password"},
 *             @OA\Property(property="full_name", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
 *             @OA\Property(property="phone_number", type="string", example="0123456789"),
 *             @OA\Property(property="password", type="string", example="Password123")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User registered successfully, verification email sent",
 *         @OA\JsonContent(
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="full_name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
 *                 @OA\Property(property="phone_number", type="string", example="0123456789"),
 *                 @OA\Property(property="role", type="string", example="user"),
 *                 @OA\Property(property="email_verified_at", type="string", format="date-time", example=null)
 *             ),
 *             @OA\Property(property="token", type="string", example="1|abcd1234tokenexample"),
 *             @OA\Property(property="message", type="string", example="Please verify your email address.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The email field is required.")
 *         )
 *     )
 * )
 */
public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'full_name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'phone_number' => [
            'required',
            'regex:/^\d{10}$/',
            'unique:users'
        ],
        'password' => [
            'required',
            'string',
            'min:8',
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
        ],
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $user = User::create([
        'full_name' => $request->full_name,
        'email' => $request->email,
        'phone_number' => $request->phone_number,
        'password' => Hash::make($request->password),
        'role' => 'user',
    ]);

    event(new Registered($user));
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
        'message' => 'Please verify your email address.'
    ], 201);
}

/**
 * @OA\Post(
 *     path="/api/login",
 *     summary="Authenticate a user and return a token",
 *     description="Logs in a user with email and password, returning a Sanctum token if credentials are valid and email is verified.",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email"),
 *             @OA\Property(property="password", type="string", example="password123", description="User's password")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful login",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Welcome, John Doe", description="Welcome message with user's full name"),
 *             @OA\Property(property="access_token", type="string", example="1|abc123def456", description="Sanctum authentication token"),
 *             @OA\Property(property="token_type", type="string", example="Bearer", description="Token type")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation or authentication error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="The provided credentials are incorrect.", description="Error message for invalid credentials or unverified email")
 *         )
 *     )
 * )
 */
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json([
            'error' => 'The provided credentials are incorrect.'
        ], 422);
    }

    $user = User::where('email', $request->email)->firstOrFail();

    if (!$user->hasVerifiedEmail()) {
        return response()->json([
            'error' => 'Please verify your email address before logging in.'
        ], 422);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Welcome, ' . $user->full_name,
        'access_token' => $token,
        'token_type' => 'Bearer',
    ]);
}
/**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Log out the authenticated user",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - No valid token provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
    // Revoke the current token used in this request
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Successfully logged out'
    ], 200);
    }

/**
 * @OA\Get(
 *     path="/api/user",
 *     summary="Get the authenticated user's details",
 *     tags={"Authentication"},
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="User retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="full_name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
 *                 @OA\Property(property="phone_number", type="string", example="0123456789"),
 *                 @OA\Property(property="role", type="string", example="user"),
 *                 @OA\Property(property="picture", type="string", example="https://example.com/pic.jpg", nullable=true),
 *                 @OA\Property(property="address", type="string", example="123 Main St", nullable=true),
 *                 @OA\Property(property="city", type="string", example="New York", nullable=true),
 *                 @OA\Property(property="email_verified_at", type="string", format="date-time", example=null),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-23T12:00:00Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-23T12:00:00Z")
 *             ),
 *             @OA\Property(property="message", type="string", example="User retrieved successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated - No valid token provided",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated")
 *         )
 *     )
 * )
 */
    public function getUser(Request $request)
    {
        // Return the authenticated user
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'user' => $user,
            'message' => 'User retrieved successfully'
        ], 200);
    }
   
    
    /**
 * @OA\Post(
 *     path="/api/user/update",
 *     summary="Update authenticated user's profile",
 *     description="Updates the authenticated user's profile, including an optional profile picture upload.",
 *     tags={"Authentication"},
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="full_name", type="string", maxLength=255, example="John Doe", description="User's full name"),
 *                 @OA\Property(property="email", type="string", format="email", maxLength=255, example="john.doe@example.com", description="User's email (must be unique)"),
 *                 @OA\Property(property="phone_number", type="string", pattern="^\d{10}$", example="0123456789", description="User's 10-digit phone number (must be unique)"),
 *                 @OA\Property(property="picture", type="string", format="binary", description="Profile picture file (JPEG/PNG/JPG, max 2MB)"),
 *                 @OA\Property(property="address", type="string", maxLength=255, example="123 Main St", description="User's address", nullable=true),
 *                 @OA\Property(property="city", type="string", maxLength=255, example="New York", description="User's city", nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Profile updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="full_name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
 *                 @OA\Property(property="picture", type="string", example="/storage/pictures/user_1_123456789.jpg"),
 *                 @OA\Property(property="phone_number", type="string", example="0123456789"),
 *                 @OA\Property(property="address", type="string", example="123 Main St", nullable=true),
 *                 @OA\Property(property="city", type="string", example="New York", nullable=true)
 *             ),
 *             @OA\Property(property="message", type="string", example="User updated successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="full_name", type="array", @OA\Items(type="string", example="The full name must be a string.")),
 *             @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken.")),
 *             @OA\Property(property="picture", type="array", @OA\Items(type="string", example="The picture must be an image."))
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Unauthenticated", description="Error message for unauthenticated access")
 *         )
 *     )
 * )
 */
     public function update(Request $request)
     {
         $user = $request->user();
     
         if (!$user) {
             return response()->json(['message' => 'Unauthenticated'], 401);
         }
     
         $validator = Validator::make($request->all(), [
             'full_name' => 'sometimes|string|max:255',
             'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
             'phone_number' => [
                 'sometimes',
                 'regex:/^\d{10}$/',
                 'unique:users,phone_number,' . $user->id,
             ],
             'picture' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
             'address' => 'nullable|string|max:255',
             'city' => 'nullable|string|max:255',
         ]);
     
         if ($validator->fails()) {
             return response()->json($validator->errors(), 400);
         }
     
         $data = $request->only(['full_name', 'email', 'phone_number', 'address', 'city']);
         if ($request->hasFile('picture')) {
             if ($user->picture && Storage::disk('public')->exists(str_replace('/storage/', '', $user->picture))) {
                 Storage::disk('public')->delete(str_replace('/storage/', '', $user->picture));
             }
             $file = $request->file('picture');
             $path = $file->storeAs('pictures', "user_{$user->id}_" . time() . '.' . $file->extension(), 'public');
             $data['picture'] = Storage::url($path);
         }
     
         $user->update($data);
     
         return response()->json([
             'user' => $user,
             'message' => 'User updated successfully'
         ], 200);
     }
    /**
 * @OA\Put(
 *     path="/api/user/update-password",
 *     summary="Update authenticated user's password",
 *     tags={"Authentication"},
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"current_password", "new_password"},
 *             @OA\Property(property="current_password", type="string", example="Password123"),
 *             @OA\Property(property="new_password", type="string", example="NewPassword456")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Password updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Password updated successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error or incorrect current password",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The current password is incorrect.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated - No valid token provided",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated")
 *         )
 *     )
 * )
 */
public function updatePassword(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $validator = Validator::make($request->all(), [
        'current_password' => 'required|string',
        'new_password' => [
            'required',
            'string',
            'min:8',
            'regex:/[A-Z]/', // At least one uppercase letter
            'regex:/[0-9]/', // At least one number
            'different:current_password', // Must differ from current password
        ],
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    // Check if the current password matches
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json(['message' => 'The current password is incorrect.'], 400);
    }

    // Update the password
    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json(['message' => 'Password updated successfully'], 200);
}   




/**
 * @OA\Get(
 *     path="/api/email/verify/{id}/{hash}",
 *     summary="Verify a user's email address",
 *     tags={"Authentication"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer"),
 *         description="The user ID"
 *     ),
 *     @OA\Parameter(
 *         name="hash",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="The email verification hash"
 *     ),
 *     @OA\Parameter(
 *         name="expires",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="integer"),
 *         description="Expiration timestamp"
 *     ),
 *     @OA\Parameter(
 *         name="signature",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="Signature for URL validation"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Email verified successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Email verified successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid verification link",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Invalid verification link")
 *         )
 *     )
 * )
 */
public function verifyEmail(Request $request, $id, $hash)
{
    $user = User::findOrFail($id);

    if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        return response()->json(['message' => 'Invalid verification link'], 400);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified'], 200);
    }

    if ($user->markEmailAsVerified()) {
        event(new \Illuminate\Auth\Events\Verified($user));
    }

    return response()->json(['message' => 'Email verified successfully'], 200);
}
}
