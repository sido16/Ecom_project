<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;


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
 *     summary="User login",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
 *             @OA\Property(property="password", type="string", example="Password123")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Welcome, John Doe"),
 *             @OA\Property(property="access_token", type="string", example="1|abcd1234tokenexample"),
 *             @OA\Property(property="token_type", type="string", example="Bearer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error or unverified email",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The provided credentials are incorrect."),
 *             @OA\Property(property="message", type="string", example="Please verify your email address before logging in.")
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

    \Log::info('Login attempt for email: ' . $request->email);

    if (!Auth::attempt($request->only('email', 'password'))) {
        \Log::info('Credentials incorrect for: ' . $request->email);
        return response()->json([
            'message' => 'The provided credentials are incorrect.'
        ], 422);
    }

    $user = User::where('email', $request->email)->firstOrFail();

    \Log::info('User found: ' . $user->email . ', Verified: ' . ($user->hasVerifiedEmail() ? 'Yes' : 'No'));

    if (!$user->hasVerifiedEmail()) {
        \Log::info('Email not verified for: ' . $user->email);
        return response()->json([
            'message' => 'Please verify your email address before logging in.'
        ], 422);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    \Log::info('Token generated for: ' . $user->email);

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
     * @OA\Put(
     *     path="/api/user/update",
     *     summary="Update authenticated user's information",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="full_name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="janedoe@example.com"),
     *             @OA\Property(property="phone_number", type="string", example="9876543210"),
     *             @OA\Property(property="picture", type="string", example="https://example.com/newpic.jpg", nullable=true),
     *             @OA\Property(property="address", type="string", example="456 Oak Ave", nullable=true),
     *             @OA\Property(property="city", type="string", example="Los Angeles", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="full_name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", example="janedoe@example.com"),
     *                 @OA\Property(property="phone_number", type="string", example="9876543210"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="picture", type="string", example="https://example.com/newpic.jpg", nullable=true),
     *                 @OA\Property(property="address", type="string", example="456 Oak Ave", nullable=true),
     *                 @OA\Property(property="city", type="string", example="Los Angeles", nullable=true),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", example=null),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-23T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-23T12:05:00Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="User updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed")
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
            'picture' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Update only the fields provided in the request
        $user->update($request->only([
            'full_name',
            'email',
            'phone_number',
            'picture',
            'address',
            'city'
        ]));

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
