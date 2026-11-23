<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'SIPENTAR API Documentation',
    description: 'API Documentation untuk Sistem Informasi Pertanian (SIPENTAR) - Aplikasi Manajemen Budidaya Hidroponik',
    contact: new OA\Contact(name: 'SIPENTAR Team')
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local Development Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
class AuthController extends Controller
{
    use ApiResponse;

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Login user',
        description: 'Authenticate user dengan username dan password. Return user data dan access token.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'admin'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'admin123', minLength: 8),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'user',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'nama', type: 'string', example: 'Super Admin'),
                                        new OA\Property(property: 'username', type: 'string', example: 'admin'),
                                        new OA\Property(property: 'role', type: 'string', enum: ['pemilik', 'pegawai'], example: 'pemilik'),
                                    ]
                                ),
                                new OA\Property(property: 'token', type: 'string', example: '1|AbCdEfGhIjKlMnOpQrStUvWxYz'),
                                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Username atau password salah.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'username',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The username field is required.')
                                ),
                                new OA\Property(
                                    property: 'password',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The password must be at least 8 characters.')
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 429,
                description: 'Too many requests (Rate limit exceeded)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Too Many Attempts.'),
                    ]
                )
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('username', $request->username)->first();
        
        // Use dummy hash to prevent timing attack when user doesn't exist
        $password = $user?->password ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

        if (! $user || ! Hash::check($request->password, $password)) {
            return $this->errorResponse('Username atau password salah.', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil.');
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Logout user',
        description: 'Revoke current access token. User harus login ulang untuk mendapatkan token baru.',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout berhasil.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(message: 'Logout berhasil.');
    }
}
