<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

class JobPublicController extends Controller
{
    /**
     * Serve the public job detail page with Open Graph meta tags.
     *
     * For social media crawlers (Facebook, LinkedIn, etc.) this returns
     * a fully OG-tagged page so the job preview card renders correctly.
     *
     * For normal browsers this returns the standard SPA shell (Vue will hydrate).
     */
    public function show(Request $request, Job $job)
    {
        // 404 for non-published jobs
        if ($job->status !== 'published' || ! $job->published_at) {
            abort(404);
        }

        // Eager-load owner and profiles for OG tag data
        $job->load(['owner' => function ($q) {
            $q->select('id', 'email');
        }, 'owner.employerProfile:id,user_id,business_name', 'owner.agencyProfile:id,user_id,agency_name']);

        $isCrawler = $request->attributes->get('_social_crawler', false);

        // For crawlers: return OG-tagged HTML
        if ($isCrawler) {
            return $this->crawlerResponse($job);
        }

        // For normal browsers: return the SPA shell (Vue handles the rest)
        return $this->spaResponse($job);
    }

    /**
     * Return an OG-tagged page for social media crawlers.
     */
    protected function crawlerResponse(Job $job)
    {
        $url = url("/candidate/jobs/{$job->id}");
        $title = $job->title;
        $description = $this->truncate(
            strip_tags($job->description ?? ''),
            160
        );

        // Build the employer/company name if available
        $employer = '';
        if ($job->owner) {
            $employer = $job->owner->employerProfile?->business_name
                ?? $job->owner->agencyProfile?->agency_name
                ?? $job->owner->email;
        }

        // Location string
        $location = collect([$job->city, $job->country])
            ->filter()
            ->implode(', ');

        if ($location) {
            $title = "{$job->title} — {$location}";
        }

        $siteName = config('app.name', 'ClinForce AI');
        $logoUrl = url('/logo.png'); // fallback if no og:image

        return view('jobs.public', compact(
            'job', 'url', 'title', 'description',
            'employer', 'location', 'siteName', 'logoUrl'
        ));
    }

    /**
     * Return the standard SPA shell for normal browsers.
     */
    protected function spaResponse(Job $job)
    {
        return view('app');
    }

    /**
     * Truncate a string to a given length.
     */
    protected function truncate(string $value, int $limit): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit)) . '…';
    }
}
