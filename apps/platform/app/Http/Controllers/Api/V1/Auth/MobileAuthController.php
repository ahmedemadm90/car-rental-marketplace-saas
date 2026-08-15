<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MobileLoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

final class MobileAuthController extends Controller
{
    public function login(MobileLoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $throttleKey = strtolower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'type' => 'https://voyagerrent.example/problems/authentication-throttled',
                'title' => 'Too many sign-in attempts.',
                'status' => 429,
                'detail' => 'Please wait before trying again.',
            ], 429)->header('Retry-After', (string) RateLimiter::availableIn($throttleKey));
        }

        $user = User::query()->where('email', $data['email'])->first();

        if ($user === null || $user->status !== 'active' || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'type' => 'https://voyagerrent.example/problems/invalid-credentials',
                'title' => 'The supplied credentials are invalid.',
                'status' => 422,
            ], 422);
        }

        RateLimiter::clear($throttleKey);

        $user->devices()->updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'platform' => $data['platform'],
                'push_token' => $data['push_token'] ?? null,
                'app_version' => $data['app_version'],
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );

        $token = JWTAuth::claims([
            'device_id' => $data['device_id'],
            'platform' => $data['platform'],
        ])->fromUser($user);

        return $this->respondWithToken($token, $user);
    }

    public function refresh(Request $request): JsonResponse
    {
        return $this->respondWithToken(JWTAuth::parseToken()->refresh(), $request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $deviceId = $payload->get('device_id');

        if (is_string($deviceId)) {
            $request->user()->devices()->where('device_id', $deviceId)->update(['revoked_at' => now()]);
        }

        JWTAuth::parseToken()->invalidate();

        return response()->json(status: 204);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->loadMissing('companies'));
    }

    private function respondWithToken(string $token, User $user): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => new UserResource($user->loadMissing('companies')),
        ]);
    }
}
