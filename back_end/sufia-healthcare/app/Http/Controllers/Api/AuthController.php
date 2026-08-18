<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Sufia Healthcare REST API',
    description: 'Laravel 12 REST API Documentation'
)]

#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum'
)]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/register',
        summary: 'Register User',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: [
                'name',
                'email',
                'password',
                'password_confirmation'
            ],
            properties: [
                new OA\Property(
                    property: 'name',
                    type: 'string',
                    example: 'SD'
                ),

                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    example: 'sd@gmail.com'
                ),

                new OA\Property(
                    property: 'password',
                    type: 'string',
                    example: '12345678'
                ),

                new OA\Property(
                    property: 'password_confirmation',
                    type: 'string',
                    example: '12345678'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Registration successful'
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error'
    )]
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 2,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }


    #[OA\Post(
        path: '/api/v1/login',
        summary: 'Login User',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    example: 'sd@gmail.com'
                ),

                new OA\Property(
                    property: 'password',
                    type: 'string',
                    example: '12345678'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Login successful'
    )]
    #[OA\Response(
        response: 422,
        description: 'Invalid credentials'
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {

            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);

        }

        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }


    #[OA\Get(
        path: '/api/v1/profile',
        summary: 'Get Logged In User Profile',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Profile fetched successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    public function profile(Request $request)
    {
        return response()->json([
            'status' => true,
            'data' => $request->user(),
        ]);
    }


    #[OA\Post(
        path: '/api/v1/logout',
        summary: 'Logout User',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Logout successful'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout successful',
        ]);
    }
}