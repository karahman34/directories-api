<?php

namespace App\Http\Controllers;

use App\Events\UserRegistered;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Helpers\Transformer;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
    * Get a JWT via given credentials.
    *
    * @param  Request  $request
    *
    * @return \Illuminate\Http\JsonResponse
    */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        try {
            $credentials = $request->only(['email', 'password']);
            $rememberToken = Str::random(40);

            if (! $token = Auth::claims(['token' => $rememberToken])->attempt($credentials)) {
                return Transformer::failed('Email or Password is wrong.', null, 401);
            }

            Auth::user()->update([
                'token' => $rememberToken,
            ]);

            return Transformer::success(
                'Success to authenticate user.',
                array_merge(
                    $this->respondWithToken($token),
                    ['user' => new UserResource(Auth::user())],
                )
            );
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to authenticate user.');
        }
    }

    /**
     * Register new user.
     *
     * @param   Request  $request
     *
     * @return  \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:255|confirmed'
        ]);

        try {
            $payload = $request->only(['name', 'email']);
            $payload['password'] = Hash::make($request->input('password'));

            $user = User::create($payload);
            $token = Auth::login($user);

            event(new UserRegistered($user));

            return Transformer::success(
                'Success to register user.',
                array_merge(
                    $this->respondWithToken($token),
                    ['user' => new UserResource($user)],
                ),
                201
            );
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to register user.');
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        try {
            return Transformer::success('Success to get authenticated user.', new UserResource(Auth::user()));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to get authenticated user.');
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            Auth::user()->update([
                'token' => null
            ]);
            
            Auth::logout();

            return Transformer::success('Success to logout user.');
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to logout user.');
        }
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return array
     */
    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ];
    }
}
