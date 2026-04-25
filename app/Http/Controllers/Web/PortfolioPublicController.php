<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\User;
use App\Models\ApplicantProfile;
use Illuminate\Http\Request;

class PortfolioPublicController extends Controller
{
    public function show(Request $request, int $userId)
    {
        // Get user and their public portfolio items
        $user = User::findOrFail($userId);
        $applicantProfile = ApplicantProfile::where('user_id', $userId)->first();
        
        $portfolioItems = Portfolio::where('user_id', $userId)
            ->where('is_public', true)
            ->orderBy('display_order')
            ->orderBy('created_at', 'desc')
            ->get();

        // Increment views for each item
        $portfolioItems->each->incrementViews();

        // Prepare meta data for social sharing
        $title = $applicantProfile ? 
            "{$applicantProfile->first_name} {$applicantProfile->last_name} - Professional Portfolio" : 
            "{$user->name} - Professional Portfolio";
            
        $description = $applicantProfile && $applicantProfile->bio ? 
            substr($applicantProfile->bio, 0, 160) : 
            "View {$user->name}'s professional portfolio showcasing their work, certifications, and achievements.";

        // Get featured image (first image portfolio item)
        $featuredImage = $portfolioItems->where('type', 'image')->where('is_featured', true)->first() 
            ?? $portfolioItems->where('type', 'image')->first();

        return view('portfolio-public', [
            'user' => $user,
            'applicantProfile' => $applicantProfile,
            'portfolioItems' => $portfolioItems,
            'pageTitle' => $title,
            'pageDescription' => $description,
            'featuredImage' => $featuredImage?->media_url,
            'portfolioStats' => [
                'total_items' => $portfolioItems->count(),
                'featured_items' => $portfolioItems->where('is_featured', true)->count(),
                'total_views' => $portfolioItems->sum('views'),
                'categories' => $portfolioItems->pluck('category')->filter()->unique()->values()
            ]
        ]);
    }
}