<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AsgardeoScimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Get the access token from request header
     */
    private function getAccessToken(Request $request): ?string
    {
        // Primary: Get from Authorization Bearer token (standard header)
        $authHeader = $request->header('Authorization');
        if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            \Log::info("Token extracted from Authorization header");
            return $token;
        }

        // Fallback: Check X-Auth-Token header
        $token = $request->header('X-Auth-Token');
        if ($token) {
            \Log::info("Token extracted from X-Auth-Token header");
            return $token;
        }

        \Log::warning("No access token found in request headers");
        return null;
    }

    /**
     * List all users from Asgardeo
     */
    public function index(Request $request): JsonResponse
    {
        try {
            \Log::info("UserController@index - Request headers: ", [
                'authorization' => $request->header('Authorization') ? 'present' : 'missing',
                'x-auth-token' => $request->header('X-Auth-Token') ? 'present' : 'missing',
            ]);

            $token = $this->getAccessToken($request);
            if (!$token) {
                \Log::error("No access token found in request");
                return response()->json([
                    'error' => 'Authentication required',
                    'message' => 'No access token provided',
                ], 401);
            }

            \Log::info("Token found, initializing SCIM service");
            $scimService = new AsgardeoScimService($token);

            $params = [];
            // Support pagination if needed
            if ($request->has('startIndex')) {
                $params['startIndex'] = $request->input('startIndex');
            }
            if ($request->has('count')) {
                $params['count'] = $request->input('count');
            }
            if ($request->has('filter')) {
                $params['filter'] = $request->input('filter');
            }

            $users = $scimService->listUsers($params);

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error("Error listing users: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch users',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific user
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $token = $this->getAccessToken($request);
            if (!$token) {
                return response()->json([
                    'error' => 'Authentication required',
                    'message' => 'No access token provided',
                ], 401);
            }

            $scimService = new AsgardeoScimService($token);
            $user = $scimService->getUser($id);
            return response()->json($user);
        } catch (\Exception $e) {
            Log::error("Error getting user: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch user',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            \Log::info("UserController@store - Request headers: ", [
                'authorization' => $request->header('Authorization') ? 'present' : 'missing',
                'x-auth-token' => $request->header('X-Auth-Token') ? 'present' : 'missing',
            ]);
            
            $token = $this->getAccessToken($request);
            if (!$token) {
                return response()->json([
                    'error' => 'Authentication required',
                    'message' => 'No access token provided',
                ], 401);
            }

            $validated = $request->validate([
                'userName' => 'required|string',
                'email' => 'required|email',
                'givenName' => 'required|string',
                'familyName' => 'required|string',
                'password' => 'required|string|min:8',
                'active' => 'sometimes|boolean',
            ]);

            $scimService = new AsgardeoScimService($token);
            $user = $scimService->createUser($validated);

            return response()->json($user, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error creating user: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create user',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a user
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $token = $this->getAccessToken($request);
            if (!$token) {
                return response()->json([
                    'error' => 'Authentication required',
                    'message' => 'No access token provided',
                ], 401);
            }

            $validated = $request->validate([
                'email' => 'sometimes|email',
                'givenName' => 'sometimes|string',
                'familyName' => 'sometimes|string',
                'active' => 'sometimes|boolean',
            ]);

            $scimService = new AsgardeoScimService($token);
            $user = $scimService->updateUser($id, $validated);

            return response()->json($user);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error updating user: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update user',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a user
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $token = $this->getAccessToken($request);
            if (!$token) {
                return response()->json([
                    'error' => 'Authentication required',
                    'message' => 'No access token provided',
                ], 401);
            }

            $scimService = new AsgardeoScimService($token);
            $scimService->deleteUser($id);

            return response()->json([
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting user: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to delete user',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
