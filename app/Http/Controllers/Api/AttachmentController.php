<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Articles\Attachment;
use App\Http\Requests\Articles\AttachmentCreateRequest;
use App\Services\Attachment\AttachmentUploadService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentController extends Controller
{

    protected Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('public');
    }


    /**
     * Get list of attachments
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Attachment::parents()
            ->with(['user', 'tags'])
            ->orderBy('created_at', 'desc');

        // Text search by tag names (tag translations)
        if ($request->filled('search')) {
            $search = $request->search;
            $locale = app()->getLocale();

            $query->whereHas('tags.translations', function ($q) use ($search, $locale) {
                $q->where('title', 'like', "%{$search}%");

                if ($locale) {
                    $q->where('locale', $locale);
                }
            });
        }

        $tagIds = $request->input('tag_ids', []);
        if (!empty($tagIds)) {
            $tagIds = is_array($tagIds) ? $tagIds : (array) $tagIds;
            $tagIds = array_filter(array_map('intval', $tagIds));
            if (!empty($tagIds)) {
                $query->whereHas('tags', function ($q) use ($tagIds) {
                    $q->whereIn('tags.id', $tagIds);
                });
            }
        }

        // Filter by mime type
        if ($request->has('mime_type') && $request->mime_type) {
            if ($request->mime_type === 'image') {
                $query->where('mime_type', 'like', 'image/%');
            } else {
                $query->where('mime_type', $request->mime_type);
            }
        }


        $user = backpack_user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'data'    => [],
            ]);
        }

        if (!$user->hasRole('Admin', 'web')) {
            $query->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('is_public', true);
            });
        }

        $perPage = $request->get('per_page', 20);
        $attachments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $attachments->items(),
            'pagination' => [
                'current_page' => $attachments->currentPage(),
                'last_page' => $attachments->lastPage(),
                'per_page' => $attachments->perPage(),
                'total' => $attachments->total(),
            ],
        ]);
    }


    /**
     * @param AttachmentCreateRequest $request
     * @param AttachmentUploadService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(
        AttachmentCreateRequest $request,
        AttachmentUploadService $service
    ) {
        try {

            $attachment = $service->upload(
                $request->file('file'),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'data' => $attachment,
            ]);

        } catch (\Throwable $e) {

            Log::error('Attachment upload failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error',
            ], 500);
        }
    }

    /**
     * Get a specific attachment
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $attachment = Attachment::parents()->with('user')->findOrFail($id);

        abort_unless($this->canAccessAttachment($attachment), 403);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $attachment->id,
                'url' => $attachment->url,
                'thumbnail_url' => $attachment->thumbnail_url,
                'filename' => $attachment->filename,
                'alt' => $attachment->alt,
                'title' => $attachment->title,
                'caption' => $attachment->caption,
                'is_public' => $attachment->is_public,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'formatted_size' => $attachment->formatted_size,
                'metadata' => $attachment->metadata,
                'user' => $attachment->user ? [
                    'id' => $attachment->user->id,
                    'name' => $attachment->user->name,
                ] : null,
                'created_at' => $attachment->created_at,
            ],
        ]);
    }

    /**
     * Update an attachment
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'alt'     => 'nullable|string|max:255',
            'title'   => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:255',
            'is_public' => 'boolean',
        ]);

        $attachment = Attachment::parents()->findOrFail($id);

        abort_unless($this->canModifyAttachment($attachment), 403);

        $data = $request->only(['alt', 'title', 'caption']);

        if (backpack_user()?->hasRole('Admin', 'web')) {
            $data['is_public'] = $request->boolean('is_public');
        }

        $attachment->update($data);

        return response()->json([
            'success' => true,
            'data' => [
                'id'       => $attachment->id,
                'url'      => $attachment->url,
                'thumbnail_url' => $attachment->thumbnail_url,
                'filename' => $attachment->filename,
                'alt'      => $attachment->alt,
                'title'    => $attachment->title,
                'caption'  => $attachment->caption,
                'is_public' => $attachment->is_public,
            ],
        ]);
    }

    /**
     * Delete an attachment
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $attachment = Attachment::parents()->findOrFail($id);

        abort_unless($this->canModifyAttachment($attachment), 403);
        
        // Delete file from storage
        if ($this->disk->exists($attachment->path)) {
            $this->disk->delete($attachment->path);
        }

        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully',
        ]);
    }

    private function canAccessAttachment(Attachment $attachment): bool
    {
        $user = backpack_user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('Admin', 'web')
            || $attachment->user_id === $user->id
            || $attachment->is_public;
    }

    private function canModifyAttachment(Attachment $attachment): bool
    {
        $user = backpack_user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('Admin', 'web')
            || $attachment->user_id === $user->id;
    }

}
