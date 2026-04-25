<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Portfolio;
use App\Models\User;

class PortfolioSeeder extends Seeder
{
    public function run()
    {
        // Get the candidate user (James Smith - Senior RN)
        $candidate = User::where('email', 'applicant1@demo.com')->first();
        
        if (!$candidate) {
            return;
        }

        $portfolioItems = [
            [
                'user_id' => $candidate->id,
                'title' => 'ICU Patient Care Excellence Award',
                'description' => 'Received recognition for outstanding patient care in the Intensive Care Unit, demonstrating exceptional clinical skills and compassionate care during critical situations.',
                'type' => 'image',
                'media_url' => '/storage/portfolio/award-certificate.jpg',
                'category' => 'Awards & Recognition',
                'tags' => ['ICU', 'Patient Care', 'Excellence', 'Award'],
                'completed_at' => '2023-12-15',
                'is_public' => true,
                'is_featured' => true,
                'display_order' => 1,
                'views' => 45
            ],
            [
                'user_id' => $candidate->id,
                'title' => 'Advanced Cardiac Life Support (ACLS) Certification',
                'description' => 'Current ACLS certification demonstrating proficiency in advanced cardiovascular life support techniques and emergency cardiac care protocols.',
                'type' => 'document',
                'media_url' => '/storage/portfolio/acls-cert.pdf',
                'category' => 'Certifications',
                'tags' => ['ACLS', 'Cardiac Care', 'Emergency', 'Certification'],
                'completed_at' => '2024-03-20',
                'is_public' => true,
                'is_featured' => true,
                'display_order' => 2,
                'views' => 32
            ],
            [
                'user_id' => $candidate->id,
                'title' => 'COVID-19 ICU Case Study: Ventilator Management',
                'description' => 'Comprehensive case study documenting successful ventilator weaning protocol for COVID-19 patients in the ICU, resulting in improved patient outcomes and reduced ventilator days.',
                'type' => 'project',
                'external_url' => 'https://example.com/case-study-covid-ventilator',
                'category' => 'Case Studies',
                'tags' => ['COVID-19', 'Ventilator', 'ICU', 'Case Study', 'Research'],
                'project_details' => [
                    'duration' => '6 months',
                    'patients' => '25 patients',
                    'outcome' => '30% reduction in ventilator days',
                    'collaboration' => 'Respiratory Therapy Team'
                ],
                'completed_at' => '2023-08-30',
                'is_public' => true,
                'is_featured' => false,
                'display_order' => 3,
                'views' => 78
            ],
            [
                'user_id' => $candidate->id,
                'title' => 'Nursing Leadership Training Certificate',
                'description' => 'Completed comprehensive leadership training program focusing on team management, conflict resolution, and quality improvement in healthcare settings.',
                'type' => 'document',
                'media_url' => '/storage/portfolio/leadership-cert.pdf',
                'category' => 'Professional Development',
                'tags' => ['Leadership', 'Management', 'Training', 'Professional Development'],
                'completed_at' => '2023-11-10',
                'is_public' => true,
                'is_featured' => false,
                'display_order' => 4,
                'views' => 23
            ],
            [
                'user_id' => $candidate->id,
                'title' => 'Patient Safety Improvement Project',
                'description' => 'Led a quality improvement initiative that reduced medication errors by 40% through implementation of barcode scanning and double-check protocols.',
                'type' => 'project',
                'external_url' => 'https://example.com/safety-project',
                'category' => 'Healthcare Projects',
                'tags' => ['Patient Safety', 'Quality Improvement', 'Medication Safety', 'Leadership'],
                'project_details' => [
                    'duration' => '4 months',
                    'team_size' => '8 nurses',
                    'outcome' => '40% reduction in medication errors',
                    'implementation' => 'Hospital-wide rollout'
                ],
                'completed_at' => '2023-06-15',
                'is_public' => true,
                'is_featured' => false,
                'display_order' => 5,
                'views' => 56
            ],
            [
                'user_id' => $candidate->id,
                'title' => 'Critical Care Nursing Presentation',
                'description' => 'Presented research findings on early mobility protocols in ICU patients at the National Critical Care Nursing Conference.',
                'type' => 'video',
                'embed_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'category' => 'Research Papers',
                'tags' => ['Research', 'ICU', 'Early Mobility', 'Conference', 'Presentation'],
                'completed_at' => '2023-09-22',
                'is_public' => true,
                'is_featured' => false,
                'display_order' => 6,
                'views' => 89
            ],
            [
                'user_id' => $candidate->id,
                'title' => 'BSN Degree - Nursing Excellence',
                'description' => 'Bachelor of Science in Nursing degree completed with magna cum laude honors, demonstrating strong academic performance and clinical competency.',
                'type' => 'document',
                'media_url' => '/storage/portfolio/bsn-degree.pdf',
                'category' => 'Certifications',
                'tags' => ['BSN', 'Degree', 'Education', 'Magna Cum Laude'],
                'completed_at' => '2020-05-15',
                'is_public' => true,
                'is_featured' => false,
                'display_order' => 7,
                'views' => 34
            ],
            [
                'user_id' => $candidate->id,
                'title' => 'Infection Control Training Materials',
                'description' => 'Developed comprehensive training materials for infection control protocols during the COVID-19 pandemic, used hospital-wide for staff education.',
                'type' => 'link',
                'external_url' => 'https://example.com/infection-control-training',
                'category' => 'Training Materials',
                'tags' => ['Infection Control', 'COVID-19', 'Training', 'Education', 'Materials'],
                'completed_at' => '2022-04-10',
                'is_public' => false,
                'is_featured' => false,
                'display_order' => 8,
                'views' => 67
            ],
            [
                'user_id' => $candidate->id,
                'title' => 'Pediatric Advanced Life Support (PALS) Certification',
                'description' => 'Current PALS certification demonstrating expertise in pediatric emergency care and advanced life support techniques for children.',
                'type' => 'document',
                'media_url' => '/storage/portfolio/pals-cert.pdf',
                'category' => 'Certifications',
                'tags' => ['PALS', 'Pediatric', 'Emergency Care', 'Certification'],
                'completed_at' => '2024-01-18',
                'is_public' => true,
                'is_featured' => false,
                'display_order' => 9,
                'views' => 28
            ],
            [
                'user_id' => $candidate->id,
                'title' => 'Nurse Mentor Program Graduate',
                'description' => 'Successfully completed the hospital\'s nurse mentorship program, developing skills in precepting new graduates and supporting professional development.',
                'type' => 'image',
                'media_url' => '/storage/portfolio/mentor-certificate.jpg',
                'category' => 'Professional Development',
                'tags' => ['Mentorship', 'Precepting', 'Professional Development', 'Leadership'],
                'completed_at' => '2023-07-25',
                'is_public' => true,
                'is_featured' => false,
                'display_order' => 10,
                'views' => 19
            ]
        ];

        foreach ($portfolioItems as $item) {
            Portfolio::create($item);
        }
    }
}