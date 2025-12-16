<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class IncidentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $incidents = Incident::query()
            ->filterBySeverity($request->input('severity'))
            ->filterByType($request->input('type'))
            ->filterByStatus($request->input('status'))
            ->search($request->input('search'))
            ->orderBy($request->input('sort_by', 'createdAt'), $request->input('sort_order', 'desc'))
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($incidents);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'required|in:Low,Medium,High',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'locationText' => 'nullable|string|max:500',
            'locationMode' => 'nullable|string',
            'date' => 'nullable|string',
            'time' => 'nullable|string',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                try {
                    $path = $file->store('incidents', 's3');
                    $photos[] = [
                        'url' => config('filesystems.disks.s3.url') . '/' . $path,
                        'key' => $path,
                        'name' => $file->getClientOriginalName(),
                    ];
                } catch (\Exception $e) {
                    Log::error('S3 upload failed: ' . $e->getMessage());
                }
            }
        }

        $incident = Incident::create([
            'type' => $validated['type'],
            'description' => $validated['description'],
            'severity' => $validated['severity'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'locationText' => $validated['locationText'] ?? null,
            'date' => $validated['date'] ?? now()->toDateString(),
            'time' => $validated['time'] ?? now()->format('H:i:s'),
            'photos' => $photos,
            'photoUrl' => $photos[0]['url'] ?? null,
            'photoKey' => $photos[0]['key'] ?? null,
        ]);

        return response()->json(new IncidentResource($incident), 201);
    }

    public function show(string $id): JsonResponse
    {
        $incident = Incident::findOrFail($id);
        return response()->json(new IncidentResource($incident));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $incident = Incident::findOrFail($id);

        $validated = $request->validate([
            'type' => 'string|max:255',
            'description' => 'string',
            'severity' => 'in:Low,Medium,High',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'locationText' => 'nullable|string|max:500',
            'status' => 'in:Open,In Progress,Resolved,Closed',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Delete old photos if new ones uploaded
        if ($request->hasFile('photos')) {
            if ($incident->photos) {
                foreach ($incident->photos as $photo) {
                    try {
                        Storage::disk('s3')->delete($photo['key']);
                    } catch (\Exception $e) {
                        Log::warning('S3 delete failed: ' . $e->getMessage());
                    }
                }
            }

            $photos = [];
            foreach ($request->file('photos') as $file) {
                try {
                    $path = $file->store('incidents', 's3');
                    $url = config('filesystems.disks.s3.url') . '/' . $path;
                    $photos[] = [
                        'url' => $url,
                        'key' => $path,
                        'name' => $file->getClientOriginalName(),
                    ];
                } catch (\Exception $e) {
                    Log::error('S3 upload failed: ' . $e->getMessage());
                }
            }
            $validated['photos'] = $photos;
            $validated['photoUrl'] = $photos[0]['url'] ?? null;
            $validated['photoKey'] = $photos[0]['key'] ?? null;
        }

        $incident->update($validated);
        return response()->json(new IncidentResource($incident));
    }

    public function destroy(string $id): JsonResponse
    {
        $incident = Incident::findOrFail($id);

        // Delete photos from S3
        if ($incident->photos) {
            foreach ($incident->photos as $photo) {
                try {
                    Storage::disk('s3')->delete($photo['key']);
                } catch (\Exception $e) {
                    Log::warning('S3 delete failed: ' . $e->getMessage());
                }
            }
        }

        $incident->delete();
        return response()->json(['message' => 'Incident deleted successfully']);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'total' => Incident::count(),
            'bySeverity' => Incident::selectRaw('severity, count(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity'),
            'byType' => Incident::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ]);
    }
}
