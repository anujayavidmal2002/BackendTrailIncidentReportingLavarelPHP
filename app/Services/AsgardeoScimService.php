<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsgardeoScimService
{
    private string $baseUrl;
    private string $accessToken;
    private string $scimEndpoint;

    public function __construct(?string $accessToken = null)
    {
        $this->baseUrl = config('services.asgardeo.base_url');
        // Use provided token or fall back to config
        $this->accessToken = $accessToken ?? config('services.asgardeo.access_token');
        $this->scimEndpoint = "{$this->baseUrl}/scim2/Users";
    }

    /**
     * List all users from Asgardeo SCIM API
     */
    public function listUsers(array $params = []): array
    {
        try {
            Log::info("AsgardeoScimService::listUsers - Making SCIM API call", [
                'endpoint' => $this->scimEndpoint,
                'params' => $params,
                'token_present' => !!$this->accessToken,
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->withoutVerifying() // Allow self-signed or invalid certs for development
                ->get($this->scimEndpoint, $params);

            Log::info("SCIM listUsers response status: {$response->status()}");

            if ($response->failed()) {
                Log::error("SCIM list users failed: {$response->status()}", [
                    'body' => $response->body(),
                    'params' => $params,
                ]);
                throw new \Exception("Failed to list users: {$response->status()}");
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("SCIM list users error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single user from Asgardeo SCIM API
     */
    public function getUser(string $userId): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->withoutVerifying()
                ->get("{$this->scimEndpoint}/{$userId}");

            if ($response->failed()) {
                Log::error("SCIM get user failed: {$response->status()}", ['userId' => $userId]);
                throw new \Exception("Failed to get user: {$response->status()}");
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("SCIM get user error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new user in Asgardeo via SCIM API
     */
    public function createUser(array $userData): array
    {
        try {
            $payload = [
                'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
                'userName' => $userData['userName'] ?? null,
                'emails' => [
                    ['value' => $userData['email'] ?? null, 'primary' => true]
                ],
                'name' => [
                    'givenName' => $userData['givenName'] ?? null,
                    'familyName' => $userData['familyName'] ?? null,
                ],
                'password' => $userData['password'] ?? null,
                'active' => $userData['active'] ?? true,
            ];

            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->withoutVerifying()
                ->post($this->scimEndpoint, $payload);

            if ($response->failed()) {
                Log::error("SCIM create user failed: {$response->status()}", [
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);
                throw new \Exception("Failed to create user: {$response->status()}");
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("SCIM create user error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a user in Asgardeo via SCIM API
     */
    public function updateUser(string $userId, array $userData): array
    {
        try {
            $payload = [
                'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            ];

            // Add fields that are being updated
            if (isset($userData['email'])) {
                $payload['emails'] = [['value' => $userData['email'], 'primary' => true]];
            }
            if (isset($userData['givenName']) || isset($userData['familyName'])) {
                $payload['name'] = [];
                if (isset($userData['givenName'])) {
                    $payload['name']['givenName'] = $userData['givenName'];
                }
                if (isset($userData['familyName'])) {
                    $payload['name']['familyName'] = $userData['familyName'];
                }
            }
            if (isset($userData['active'])) {
                $payload['active'] = $userData['active'];
            }

            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->withoutVerifying()
                ->put("{$this->scimEndpoint}/{$userId}", $payload);

            if ($response->failed()) {
                Log::error("SCIM update user failed: {$response->status()}", [
                    'body' => $response->body(),
                    'userId' => $userId,
                ]);
                throw new \Exception("Failed to update user: {$response->status()}");
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("SCIM update user error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a user from Asgardeo via SCIM API
     */
    public function deleteUser(string $userId): bool
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->withoutVerifying()
                ->delete("{$this->scimEndpoint}/{$userId}");

            if ($response->failed()) {
                Log::error("SCIM delete user failed: {$response->status()}", ['userId' => $userId]);
                throw new \Exception("Failed to delete user: {$response->status()}");
            }

            return true;
        } catch (\Exception $e) {
            Log::error("SCIM delete user error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get common headers for SCIM API requests
     */
private function getHeaders(): array
{
    // Ensure we don't double up on the "Bearer" prefix
    $token = str_replace('Bearer ', '', $this->accessToken);
    
    return [
        'Authorization' => "Bearer " . $token,
        'Content-Type' => 'application/scim+json',
        'Accept' => 'application/scim+json',
    ];
}
}
