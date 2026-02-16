<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class SafeContent implements Rule
{
    protected string $failReason = '';

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $value = (string) $value;

        // 1. Email detection
        // Matches typical email patterns
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $value)) {
            $this->failReason = 'Email addresses are not allowed.';
            return false;
        }

        // 2. Phone detection
        // Matches common formats: 123-456-7890, (123) 456-7890, +1 123-456-7890
        if (preg_match('/\b(?:\+?\d{1,3}[-. ]?)?\(?\d{3}\)?[-. ]?\d{3}[-. ]?\d{4}\b/', $value)) {
            $this->failReason = 'Phone numbers are not allowed.';
            return false;
        }

        // 3. Social Media detection
        $domains = [
            'facebook.com', 'instagram.com', 'linkedin.com', 'twitter.com', 'x.com',
            'tiktok.com', 'snapchat.com', 'pinterest.com', 't.me', 'discord.gg',
            'whatsapp.com', 'wa.me', 'youtube.com'
        ];
        
        foreach ($domains as $domain) {
            if (stripos($value, $domain) !== false) {
                $this->failReason = 'Social media links are not allowed.';
                return false;
            }
        }

        // 4. Full Name detection (only for Applicants)
        $user = Auth::user();
        if ($user && $user->role === 'applicant') {
            // Ensure profile is loaded
            if (!$user->relationLoaded('applicantProfile')) {
                $user->load('applicantProfile');
            }
            
            $profile = $user->applicantProfile;
            if ($profile) {
                $first = trim($profile->first_name ?? '');
                $last = trim($profile->last_name ?? '');
                
                if ($first && $last) {
                    $fullName = $first . ' ' . $last;
                    // Only block if the name is reasonably long to avoid false positives with short names
                    if (mb_strlen($fullName) >= 5 && stripos($value, $fullName) !== false) {
                        $this->failReason = 'Sharing your full name is not allowed.';
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'Restricted content detected: ' . $this->failReason;
    }
}
