<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectSocialCrawler
{
    /**
     * Known social media crawler user-agent fragments.
     */
    protected static array $crawlers = [
        'facebookexternalhit',
        'Facebot',
        'LinkedInBot',
        'Twitterbot',
        'Slackbot',
        'Discordbot',
        'WhatsApp',
        'TelegramBot',
        'Pinterest',
        'embedly',
        'showyoubot',
        'outbrain',
        'developers\.google\.com',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $userAgent = $request->userAgent() ?? '';

        $isCrawler = false;
        foreach (self::$crawlers as $pattern) {
            if (preg_match('/' . $pattern . '/i', $userAgent)) {
                $isCrawler = true;
                break;
            }
        }

        $request->attributes->set('_social_crawler', $isCrawler);

        return $next($request);
    }
}
