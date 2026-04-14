<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearningDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        // Skills Catalog
        $skills = [
            ['name' => 'Patient Care', 'category' => 'clinical', 'specialty' => 'nursing', 'description' => 'Comprehensive patient care and assessment skills', 'importance_score' => 95, 'requires_certification' => false],
            ['name' => 'IV Therapy', 'category' => 'clinical', 'specialty' => 'nursing', 'description' => 'Intravenous therapy administration and management', 'importance_score' => 85, 'requires_certification' => true, 'certification_body' => 'INS'],
            ['name' => 'Emergency Response', 'category' => 'clinical', 'specialty' => 'nursing', 'description' => 'Emergency and critical care response protocols', 'importance_score' => 90, 'requires_certification' => true, 'certification_body' => 'AHA'],
            ['name' => 'Medical Documentation', 'category' => 'technical', 'specialty' => 'nursing', 'description' => 'Electronic health record documentation and compliance', 'importance_score' => 80, 'requires_certification' => false],
            ['name' => 'Leadership', 'category' => 'leadership', 'specialty' => null, 'description' => 'Team leadership and management skills', 'importance_score' => 75, 'requires_certification' => false],
            ['name' => 'Communication', 'category' => 'soft', 'specialty' => null, 'description' => 'Effective communication with patients and colleagues', 'importance_score' => 85, 'requires_certification' => false],
            ['name' => 'HIPAA Compliance', 'category' => 'compliance', 'specialty' => null, 'description' => 'Healthcare privacy and security regulations', 'importance_score' => 95, 'requires_certification' => true, 'certification_body' => 'HHS'],
            ['name' => 'Medication Administration', 'category' => 'clinical', 'specialty' => 'nursing', 'description' => 'Safe medication preparation and administration', 'importance_score' => 90, 'requires_certification' => false],
            ['name' => 'Wound Care', 'category' => 'clinical', 'specialty' => 'nursing', 'description' => 'Advanced wound assessment and treatment', 'importance_score' => 70, 'requires_certification' => true, 'certification_body' => 'WOCN'],
            ['name' => 'Critical Thinking', 'category' => 'soft', 'specialty' => null, 'description' => 'Clinical decision-making and problem-solving', 'importance_score' => 88, 'requires_certification' => false],
        ];

        foreach ($skills as $skill) {
            DB::table('skills_catalog')->insert([
                'name' => $skill['name'],
                'category' => $skill['category'],
                'specialty' => $skill['specialty'],
                'description' => $skill['description'],
                'importance_score' => $skill['importance_score'],
                'requires_certification' => $skill['requires_certification'],
                'certification_body' => $skill['certification_body'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Learning Providers
        $providers = [
            ['name' => 'American Nurses Credentialing Center', 'type' => 'certification_body', 'description' => 'Leading nursing certification organization', 'website_url' => 'https://www.nursingworld.org/ancc/', 'is_accredited' => true, 'offers_certificates' => true, 'offers_ceu' => true],
            ['name' => 'Coursera Health', 'type' => 'online_platform', 'description' => 'Online healthcare education platform', 'website_url' => 'https://www.coursera.org/browse/health', 'is_accredited' => true, 'offers_certificates' => true, 'offers_ceu' => false],
            ['name' => 'Medscape Education', 'type' => 'online_platform', 'description' => 'Medical education and CME provider', 'website_url' => 'https://www.medscape.org/education', 'is_accredited' => true, 'offers_certificates' => true, 'offers_ceu' => true],
            ['name' => 'Johns Hopkins University', 'type' => 'university', 'description' => 'Prestigious medical education institution', 'website_url' => 'https://www.jhu.edu/', 'is_accredited' => true, 'offers_certificates' => true, 'offers_ceu' => true],
            ['name' => 'Internal Training', 'type' => 'internal', 'description' => 'Organization-specific training programs', 'is_accredited' => false, 'offers_certificates' => true, 'offers_ceu' => false],
        ];

        foreach ($providers as $provider) {
            DB::table('learning_providers')->insert([
                'name' => $provider['name'],
                'type' => $provider['type'],
                'description' => $provider['description'],
                'website_url' => $provider['website_url'] ?? null,
                'is_accredited' => $provider['is_accredited'],
                'offers_certificates' => $provider['offers_certificates'],
                'offers_ceu' => $provider['offers_ceu'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Learning Courses
        $courses = [
            [
                'provider_id' => 1, 'title' => 'Advanced Cardiac Life Support (ACLS)', 'description' => 'Comprehensive ACLS certification course for healthcare professionals',
                'category' => 'clinical', 'specialty' => 'nursing', 'difficulty_level' => 'advanced', 'duration_hours' => 16, 'price' => 299.00,
                'format' => 'hybrid', 'offers_certificate' => true, 'offers_ceu' => true, 'ceu_hours' => 16.0, 'rating' => 4.8
            ],
            [
                'provider_id' => 2, 'title' => 'Healthcare Leadership Fundamentals', 'description' => 'Essential leadership skills for healthcare professionals',
                'category' => 'leadership', 'specialty' => null, 'difficulty_level' => 'intermediate', 'duration_hours' => 24, 'price' => 199.00,
                'format' => 'online', 'offers_certificate' => true, 'offers_ceu' => false, 'rating' => 4.5
            ],
            [
                'provider_id' => 3, 'title' => 'HIPAA Privacy and Security', 'description' => 'Comprehensive HIPAA compliance training',
                'category' => 'compliance', 'specialty' => null, 'difficulty_level' => 'beginner', 'duration_hours' => 4, 'price' => 49.00,
                'format' => 'online', 'offers_certificate' => true, 'offers_ceu' => true, 'ceu_hours' => 4.0, 'rating' => 4.3
            ],
            [
                'provider_id' => 1, 'title' => 'Wound Care Certification', 'description' => 'Advanced wound assessment and treatment techniques',
                'category' => 'clinical', 'specialty' => 'nursing', 'difficulty_level' => 'advanced', 'duration_hours' => 32, 'price' => 599.00,
                'format' => 'in_person', 'offers_certificate' => true, 'offers_ceu' => true, 'ceu_hours' => 32.0, 'rating' => 4.9
            ],
            [
                'provider_id' => 4, 'title' => 'Clinical Decision Making', 'description' => 'Evidence-based clinical decision making for nurses',
                'category' => 'clinical', 'specialty' => 'nursing', 'difficulty_level' => 'intermediate', 'duration_hours' => 20, 'price' => 399.00,
                'format' => 'online', 'offers_certificate' => true, 'offers_ceu' => true, 'ceu_hours' => 20.0, 'rating' => 4.6
            ],
            [
                'provider_id' => 2, 'title' => 'Effective Communication in Healthcare', 'description' => 'Improve patient and team communication skills',
                'category' => 'soft_skills', 'specialty' => null, 'difficulty_level' => 'beginner', 'duration_hours' => 8, 'price' => 99.00,
                'format' => 'online', 'offers_certificate' => true, 'offers_ceu' => false, 'rating' => 4.4
            ],
        ];

        foreach ($courses as $course) {
            DB::table('learning_courses')->insert([
                'provider_id' => $course['provider_id'],
                'title' => $course['title'],
                'description' => $course['description'],
                'category' => $course['category'],
                'specialty' => $course['specialty'],
                'difficulty_level' => $course['difficulty_level'],
                'duration_hours' => $course['duration_hours'],
                'price' => $course['price'],
                'format' => $course['format'],
                'offers_certificate' => $course['offers_certificate'],
                'offers_ceu' => $course['offers_ceu'],
                'ceu_hours' => $course['ceu_hours'] ?? null,
                'rating' => $course['rating'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Certification Types
        $certificationTypes = [
            [
                'name' => 'Registered Nurse License', 'abbreviation' => 'RN', 'category' => 'license', 'specialty' => 'nursing',
                'description' => 'State nursing license for registered nurses', 'issuing_organization' => 'State Board of Nursing',
                'validity_months' => 24, 'requires_renewal' => true, 'renewal_months' => 24, 'requires_ceu' => true, 'required_ceu_hours' => 30, 'is_mandatory' => true
            ],
            [
                'name' => 'Basic Life Support', 'abbreviation' => 'BLS', 'category' => 'certification', 'specialty' => 'nursing',
                'description' => 'Basic life support certification', 'issuing_organization' => 'American Heart Association',
                'validity_months' => 24, 'requires_renewal' => true, 'renewal_months' => 24, 'requires_ceu' => false, 'is_mandatory' => true
            ],
            [
                'name' => 'Advanced Cardiac Life Support', 'abbreviation' => 'ACLS', 'category' => 'certification', 'specialty' => 'nursing',
                'description' => 'Advanced cardiac life support certification', 'issuing_organization' => 'American Heart Association',
                'validity_months' => 24, 'requires_renewal' => true, 'renewal_months' => 24, 'requires_ceu' => false, 'is_mandatory' => false
            ],
            [
                'name' => 'Certified Wound Ostomy Continence Nurse', 'abbreviation' => 'CWOCN', 'category' => 'specialty', 'specialty' => 'nursing',
                'description' => 'Specialty certification in wound, ostomy, and continence care', 'issuing_organization' => 'Wound, Ostomy and Continence Nursing Certification Board',
                'validity_months' => 60, 'requires_renewal' => true, 'renewal_months' => 60, 'requires_ceu' => true, 'required_ceu_hours' => 50, 'is_mandatory' => false
            ],
            [
                'name' => 'HIPAA Privacy Certification', 'abbreviation' => 'HPC', 'category' => 'continuing_education', 'specialty' => null,
                'description' => 'Healthcare privacy and security compliance certification', 'issuing_organization' => 'Department of Health and Human Services',
                'validity_months' => 12, 'requires_renewal' => true, 'renewal_months' => 12, 'requires_ceu' => true, 'required_ceu_hours' => 4, 'is_mandatory' => true
            ],
        ];

        foreach ($certificationTypes as $cert) {
            DB::table('certification_types')->insert([
                'name' => $cert['name'],
                'abbreviation' => $cert['abbreviation'],
                'category' => $cert['category'],
                'specialty' => $cert['specialty'],
                'description' => $cert['description'],
                'issuing_organization' => $cert['issuing_organization'],
                'validity_months' => $cert['validity_months'],
                'requires_renewal' => $cert['requires_renewal'],
                'renewal_months' => $cert['renewal_months'],
                'requires_ceu' => $cert['requires_ceu'],
                'required_ceu_hours' => $cert['required_ceu_hours'] ?? null,
                'is_mandatory' => $cert['is_mandatory'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Career Paths (using existing table structure from career guidance migration)
        $careerPaths = [
            [
                'name' => 'RN to Nurse Manager',
                'category' => 'nursing',
                'from_role' => 'Staff Nurse',
                'to_role' => 'Nurse Manager',
                'description' => 'Traditional nursing career progression path from staff nurse to management',
                'typical_duration_months' => 96, // 8 years
                'avg_salary_increase' => 25000.00,
                'required_steps' => json_encode([
                    'Gain 2+ years clinical experience',
                    'Complete leadership training',
                    'Obtain BSN if not already held',
                    'Develop mentoring skills',
                    'Complete management certification'
                ]),
                'required_certifications' => json_encode(['BLS', 'ACLS', 'Leadership Certification']),
                'resources' => json_encode([
                    'Leadership courses',
                    'Management training programs',
                    'Mentorship opportunities'
                ])
            ],
            [
                'name' => 'CNA to RN',
                'category' => 'nursing',
                'from_role' => 'Certified Nursing Assistant',
                'to_role' => 'Registered Nurse',
                'description' => 'Career advancement from CNA to RN through education and training',
                'typical_duration_months' => 24, // 2 years
                'avg_salary_increase' => 20000.00,
                'required_steps' => json_encode([
                    'Complete nursing prerequisites',
                    'Apply to nursing program',
                    'Complete ADN or BSN program',
                    'Pass NCLEX-RN exam',
                    'Obtain state licensure'
                ]),
                'required_certifications' => json_encode(['RN License', 'BLS']),
                'resources' => json_encode([
                    'Nursing school programs',
                    'NCLEX preparation courses',
                    'Financial aid resources'
                ])
            ]
        ];

        foreach ($careerPaths as $path) {
            DB::table('career_paths')->insert([
                'name' => $path['name'],
                'category' => $path['category'],
                'from_role' => $path['from_role'],
                'to_role' => $path['to_role'],
                'description' => $path['description'],
                'typical_duration_months' => $path['typical_duration_months'],
                'avg_salary_increase' => $path['avg_salary_increase'],
                'required_steps' => $path['required_steps'],
                'required_certifications' => $path['required_certifications'],
                'resources' => $path['resources'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        echo "Learning & Development sample data seeded successfully!\n";
    }
}