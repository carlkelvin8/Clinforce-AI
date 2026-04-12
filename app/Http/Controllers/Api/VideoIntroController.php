<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApplicantProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VideoIntroController extends ApiController
{
    /**
     * Upload video introduction
     */
    public function upload(Request $request)
    {
        $user = $request->user();
        $profile = ApplicantProfile::where('user_id', $user->id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'video' => 'required|file|mimes:mp4,mov,avi,webm|max:102400', // 100MB max
            'duration' => 'required|integer|min:10|max:60',
            'thumbnail' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors(), 422);
        }

        // Validate duration
        $duration = $request->duration;
        if ($duration > 60) {
            return $this->fail('Video must be 60 seconds or less', 400);
        }

        // Delete old video if exists
        if ($profile->video_intro_url) {
            $oldPath = str_replace('/storage/', '', $profile->video_intro_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Store video
        $video = $request->file('video');
        $videoPath = $video->store('video-intros/' . $user->id, 'public');
        $videoUrl = Storage::url($videoPath);

        // Store thumbnail if provided
        $thumbnailUrl = $profile->video_intro_thumbnail;
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $thumbnailPath = $thumbnail->store('video-intros/' . $user->id . '/thumbs', 'public');
            $thumbnailUrl = Storage::url($thumbnailPath);
        }

        // Update profile
        $profile->update([
            'video_intro_url' => $videoUrl,
            'video_intro_thumbnail' => $thumbnailUrl,
            'video_intro_duration' => $duration,
        ]);

        // Recalculate profile completeness
        $profile->calculateProfileCompleteness();

        return $this->ok([
            'message' => 'Video introduction uploaded successfully',
            'video_url' => $videoUrl,
            'thumbnail_url' => $thumbnailUrl,
            'duration' => $duration,
        ]);
    }

    /**
     * Update video intro metadata
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $profile = ApplicantProfile::where('user_id', $user->id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|integer|min:10|max:60',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors(), 422);
        }

        if ($request->has('duration')) {
            $profile->update(['video_intro_duration' => $request->duration]);
        }

        return $this->ok($profile);
    }

    /**
     * Delete video introduction
     */
    public function destroy(Request $request)
    {
        $user = $request->user();
        $profile = ApplicantProfile::where('user_id', $user->id)->firstOrFail();

        if (!$profile->video_intro_url) {
            return $this->fail('No video introduction found', 404);
        }

        // Delete video file
        $videoPath = str_replace('/storage/', '', $profile->video_intro_url);
        if (Storage::disk('public')->exists($videoPath)) {
            Storage::disk('public')->delete($videoPath);
        }

        // Delete thumbnail
        if ($profile->video_intro_thumbnail) {
            $thumbPath = str_replace('/storage/', '', $profile->video_intro_thumbnail);
            if (Storage::disk('public')->exists($thumbPath)) {
                Storage::disk('public')->delete($thumbPath);
            }
        }

        $profile->update([
            'video_intro_url' => null,
            'video_intro_thumbnail' => null,
            'video_intro_duration' => null,
        ]);

        // Recalculate profile completeness
        $profile->calculateProfileCompleteness();

        return $this->ok(['message' => 'Video introduction deleted']);
    }

    /**
     * Get video intro (public)
     */
    public function show(int $userId)
    {
        $profile = ApplicantProfile::where('user_id', $userId)->firstOrFail();

        if (!$profile->video_intro_url) {
            return $this->fail('No video introduction available', 404);
        }

        return $this->ok([
            'video_url' => $profile->video_intro_url,
            'thumbnail_url' => $profile->video_intro_thumbnail,
            'duration' => $profile->video_intro_duration,
        ]);
    }

    /**
     * Get upload guidelines
     */
    public function guidelines()
    {
        return $this->ok([
            'max_duration' => 60,
            'min_duration' => 10,
            'max_file_size_mb' => 100,
            'supported_formats' => ['mp4', 'mov', 'avi', 'webm'],
            'thumbnail_size' => '1280x720 recommended',
            'tips' => [
                'Introduce yourself in the first 5 seconds',
                'Highlight your top 3 skills',
                'Explain what makes you unique',
                'End with a clear call-to-action',
                'Speak clearly and maintain eye contact',
                'Use good lighting and minimal background noise',
                'Dress professionally as you would for an interview',
            ],
            'example_script' => "Hi, I'm [Name], a [Role] with [X] years of experience in [Field]. My expertise includes [Skill 1], [Skill 2], and [Skill 3]. I've [Major Achievement]. I'm passionate about [What drives you]. I'm looking for opportunities where I can [Your Goal]. Thanks for watching!",
        ]);
    }
}
