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
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *                 @OA\Property(property="phone_number", type="string", example="0123456789")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|abcd1234tokenexample")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed")
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
                'regex:/^\d{10}$/', // Ensures exactly 10 digits
                'unique:users'
            ],
            'password' => [
                'required',
                'string',
                'min:8', // Minimum 8 characters
                'regex:/[A-Z]/', // At least one uppercase letter
                'regex:/[0-9]/', // At least one number
            ],
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        // Create the user
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => 'user', 
        ]);
        event(new Registered($user));
        // Generate a token
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'user' => $user,
            'token' => $token,
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
     *         response=401,
     *         description="Unauthorized - Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The provided credentials are incorrect.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Attempt to authenticate the user
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Retrieve the authenticated user
        $user = User::where('email', $request->email)->firstOrFail();

        // Generate an authentication token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return a JSON response with the token
        return response()->json([
            'message' => 'Welcome, ' . $user->name,
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
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", example=null),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-22T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-22T12:00:00Z")
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
}
