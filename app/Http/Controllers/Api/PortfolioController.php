<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\ApplicantProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PortfolioController extends ApiController
{
    /**
     * List portfolio items
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = ApplicantProfile::where('user_id', $user->id)->firstOrFail();
        
        $query = Portfolio::where('user_id', $user->id);
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $items = $query->orderBy('display_order')->orderBy('created_at', 'desc')->get();

        return $this->ok($items);
    }

    /**
     * Create portfolio item
     */
    public function store(Request $request)
    {
        $user = $request->user();
        ApplicantProfile::where('user_id', $user->id)->firstOrFail();

        // Handle tags from FormData (JSON string) or regular request (array)
        $tags = $request->input('tags');
        if (is_string($tags)) {
            $tags = json_decode($tags, true) ?: [];
        } elseif (!is_array($tags)) {
            $tags = [];
        }

        // Handle boolean fields from FormData
        $isPublic = $request->input('is_public');
        if (is_string($isPublic)) {
            $isPublic = in_array($isPublic, ['1', 'true', true], true);
        } else {
            $isPublic = (bool) $isPublic;
        }

        $isFeatured = $request->input('is_featured');
        if (is_string($isFeatured)) {
            $isFeatured = in_array($isFeatured, ['1', 'true', true], true);
        } else {
            $isFeatured = (bool) $isFeatured;
        }

        $validator = Validator::make(array_merge($request->all(), [
            'tags' => $tags,
            'is_public' => $isPublic,
            'is_featured' => $isFeatured,
        ]), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:image,video,link,document,project',
            'media_url' => 'nullable|string|max:500',
            'embed_url' => 'nullable|string|max:500',
            'external_url' => 'nullable|url|max:500',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'project_details' => 'nullable|array',
            'completed_at' => 'nullable|date',
            'is_public' => 'boolean',
            'is_featured' => 'boolean',
            'display_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors(), 422);
        }

        $validatedData = $validator->validated();

        // Handle file upload if present
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $path = $file->store('portfolios/' . $user->id, 'public');
            $validatedData['media_url'] = Storage::url($path);
        }

        $portfolio = Portfolio::create(array_merge(
            $validatedData,
            ['user_id' => $user->id]
        ));

        // Update profile completeness
        $profile = ApplicantProfile::where('user_id', $user->id)->first();
        $profile?->calculateProfileCompleteness();

        return $this->ok($portfolio, 201);
    }

    /**
     * Update portfolio item
     */
    public function update(Request $request, Portfolio $portfolio)
    {
        $user = $request->user();
        
        if ($portfolio->user_id !== $user->id) {
            return $this->fail('Unauthorized', 403);
        }

        // Handle tags from FormData (JSON string) or regular request (array)
        $tags = $request->input('tags');
        if (is_string($tags)) {
            $tags = json_decode($tags, true) ?: [];
        } elseif (!is_array($tags)) {
            $tags = [];
        }

        // Handle boolean fields from FormData
        $isPublic = $request->input('is_public');
        if (is_string($isPublic)) {
            $isPublic = in_array($isPublic, ['1', 'true', true], true);
        } else {
            $isPublic = (bool) $isPublic;
        }

        $isFeatured = $request->input('is_featured');
        if (is_string($isFeatured)) {
            $isFeatured = in_array($isFeatured, ['1', 'true', true], true);
        } else {
            $isFeatured = (bool) $isFeatured;
        }

        $validator = Validator::make(array_merge($request->all(), [
            'tags' => $tags,
            'is_public' => $isPublic,
            'is_featured' => $isFeatured,
        ]), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'sometimes|in:image,video,link,document,project',
            'media_url' => 'nullable|string|max:500',
            'embed_url' => 'nullable|string|max:500',
            'external_url' => 'nullable|url|max:500',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'project_details' => 'nullable|array',
            'completed_at' => 'nullable|date',
            'is_public' => 'boolean',
            'is_featured' => 'boolean',
            'display_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors(), 422);
        }

        $validatedData = $validator->validated();

        // Handle file upload if present
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $path = $file->store('portfolios/' . $user->id, 'public');
            $validatedData['media_url'] = Storage::url($path);
        }

        $portfolio->update($validatedData);

        return $this->ok($portfolio);
    }

    /**
     * Delete portfolio item
     */
    public function destroy(Request $request, Portfolio $portfolio)
    {
        $user = $request->user();
        
        if ($portfolio->user_id !== $user->id) {
            return $this->fail('Unauthorized', 403);
        }

        // Delete media file if exists
        if ($portfolio->media_url && str_starts_with($portfolio->media_url, '/storage/')) {
            $path = str_replace('/storage/', '', $portfolio->media_url);
            Storage::disk('public')->delete($path);
        }

        $portfolio->delete();

        // Recalculate profile completeness
        $profile = ApplicantProfile::where('user_id', $user->id)->first();
        $profile?->calculateProfileCompleteness();

        return $this->ok(['message' => 'Portfolio item deleted']);
    }

    /**
     * Reorder portfolio items
     */
    public function reorder(Request $request)
    {
        $user = $request->user();
        $order = $request->input('order', []);

        foreach ($order as $item) {
            Portfolio::where('user_id', $user->id)
                ->where('id', $item['id'])
                ->update(['display_order' => $item['order']]);
        }

        return $this->ok(['message' => 'Portfolio reordered']);
    }

    /**
     * Public view (for sharing)
     */
    public function showPublic(Request $request, int $userId)
    {
        $portfolio = Portfolio::where('user_id', $userId)
            ->where('is_public', true)
            ->with('user:id,email,role')
            ->orderBy('display_order')
            ->get();

        $portfolio->each->incrementViews();

        return $this->ok($portfolio);
    }
}
