<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZoomFilterSetting;
use Illuminate\Http\Request;

class ZoomSettingsController extends Controller
{
    /**
     * Get the current user's Zoom filter settings.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        $settings = ZoomFilterSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'filter_emails' => true,
                'filter_domains' => false,
                'monitor_audio' => false,
                'lock_name' => true,
                'privacy_filtering' => true,
                'replacement_text' => 'Participant [Filtered]',
            ]
        );

        return response()->json($settings);
    }

    /**
     * Update the current user's Zoom filter settings.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'filter_emails' => 'boolean',
            'filter_domains' => 'boolean',
            'monitor_audio' => 'boolean',
            'lock_name' => 'boolean',
            'replacement_text' => 'nullable|string|max:255',
            'blocked_domains' => 'nullable|array',
            'custom_patterns' => 'nullable|array',
        ]);

        // Ensure privacy_filtering is always true and cannot be changed
        $validated['privacy_filtering'] = true;

        $settings = ZoomFilterSetting::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'message' => 'Zoom settings updated successfully.',
            'settings' => $settings,
        ]);
    }
}
