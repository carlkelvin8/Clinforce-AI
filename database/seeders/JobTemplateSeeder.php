<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobTemplate;
use App\Models\User;

class JobTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find or create a system user (admin) to own system templates
        $adminUser = User::where('role', 'admin')->first();
        
        if (!$adminUser) {
            $this->command->error('No admin user found. Please create an admin user first.');
            return;
        }

        $templates = $this->getTemplates();

        foreach ($templates as $template) {
            JobTemplate::create(array_merge($template, [
                'owner_user_id' => $adminUser->id,
                'is_system_template' => true,
            ]));
        }

        $this->command->info(count($templates) . ' job templates seeded successfully!');
    }

    private function getTemplates(): array
    {
        return [
            // NURSING TEMPLATES
            [
                'name' => 'ER Nurse Template',
                'category' => 'nursing',
                'role_type' => 'ER Nurse',
                'tags' => ['emergency', 'ER', 'trauma', 'acute care', 'nursing'],
                'title' => 'Emergency Room Nurse (RN)',
                'description' => $this->getErNurseDescription(),
                'employment_type' => 'full-time',
                'work_mode' => 'on-site',
                'required_certifications' => ['BSN', 'RN', 'ACLS', 'PALS', 'TNCC', 'CEN'],
                'required_licenses' => ['State RN License', 'BLS Certification'],
                'shift_type' => '12hr',
                'shift_details' => [
                    'schedule' => '3x12 hour shifts per week',
                    'hours' => '7:00 AM - 7:00 PM or 7:00 PM - 7:00 AM',
                    'weekend_rotation' => true,
                    'holiday_rotation' => true,
                ],
                'experience_level' => 'mid',
                'min_experience_years' => 2,
                'benefits' => [
                    'Competitive salary with shift differentials',
                    'Health, dental, and vision insurance',
                    '401(k) with employer match',
                    'Tuition reimbursement',
                    'Continuing education allowance',
                    'Paid time off and holidays',
                    'Certification reimbursement',
                    'Employee wellness programs',
                ],
                'compliance_checklist' => [
                    'certifications' => ['BSN', 'RN', 'ACLS', 'PALS', 'TNCC'],
                    'licenses' => ['State RN License', 'BLS'],
                    'requirements' => ['Background check', 'Drug screening', 'Immunizations'],
                    'shift_compliance' => ['Mandatory overtime may apply', 'Weekend rotation required'],
                    'legal_requirements' => ['Equal Opportunity Employer', 'ADA compliant'],
                ],
            ],

            [
                'name' => 'Travel RN Template',
                'category' => 'nursing',
                'role_type' => 'Travel RN',
                'tags' => ['travel', 'contract', 'temporary', 'assignment', 'nursing'],
                'title' => 'Travel Registered Nurse (RN)',
                'description' => $this->getTravelRnDescription(),
                'employment_type' => 'contract',
                'work_mode' => 'on-site',
                'required_certifications' => ['BSN', 'RN', 'ACLS', 'BLS'],
                'required_licenses' => ['Active State RN License', 'Compact License (preferred)'],
                'shift_type' => '12hr',
                'shift_details' => [
                    'schedule' => '13-week assignments',
                    'hours' => 'Flexible - varies by assignment',
                    'extension_possible' => true,
                ],
                'experience_level' => 'mid',
                'min_experience_years' => 1,
                'benefits' => [
                    'Competitive weekly pay',
                    'Tax-free housing stipend',
                    'Travel reimbursement',
                    'Health and dental insurance',
                    '401(k) retirement plan',
                    'Licensure reimbursement',
                    'Completion bonuses',
                    'Referral bonuses',
                ],
                'compliance_checklist' => [
                    'certifications' => ['BSN', 'RN', 'ACLS', 'BLS'],
                    'licenses' => ['Active RN License', 'Compact License preferred'],
                    'requirements' => ['1+ year recent experience', 'Background check'],
                    'shift_compliance' => ['Assignment length varies', 'Extensions available'],
                    'legal_requirements' => ['Equal Opportunity Employer', 'Work authorization required'],
                ],
            ],

            [
                'name' => 'ICU Nurse Template',
                'category' => 'nursing',
                'role_type' => 'ICU Nurse',
                'tags' => ['ICU', 'critical care', 'intensive', 'nursing'],
                'title' => 'Intensive Care Unit (ICU) Nurse',
                'description' => $this->getIcuNurseDescription(),
                'employment_type' => 'full-time',
                'work_mode' => 'on-site',
                'required_certifications' => ['BSN', 'RN', 'ACLS', 'CCRN'],
                'required_licenses' => ['State RN License', 'BLS Certification'],
                'shift_type' => '12hr',
                'shift_details' => [
                    'schedule' => '3x12 hour shifts',
                    'hours' => 'Day or Night shift available',
                    'critical_care' => true,
                ],
                'experience_level' => 'mid',
                'min_experience_years' => 2,
                'benefits' => [
                    'Competitive salary',
                    'Critical care differential pay',
                    'Comprehensive health insurance',
                    'Retirement savings plan',
                    'Professional development',
                    'CCRN certification support',
                    'Paid continuing education',
                ],
                'compliance_checklist' => [
                    'certifications' => ['BSN', 'RN', 'ACLS', 'CCRN preferred'],
                    'licenses' => ['State RN License', 'BLS'],
                    'requirements' => ['ICU experience required', 'Critical care training'],
                    'shift_compliance' => ['12-hour shifts', 'Weekend/holiday rotation'],
                    'legal_requirements' => ['Equal Opportunity Employer', 'Background check required'],
                ],
            ],

            [
                'name' => 'OR Nurse Template',
                'category' => 'nursing',
                'role_type' => 'OR Nurse',
                'tags' => ['operating room', 'surgical', 'perioperative', 'nursing'],
                'title' => 'Operating Room (OR) Nurse',
                'employment_type' => 'full-time',
                'work_mode' => 'on-site',
                'required_certifications' => ['BSN', 'RN', 'CNOR', 'ACLS'],
                'required_licenses' => ['State RN License', 'BLS Certification'],
                'shift_type' => 'day',
                'shift_details' => [
                    'schedule' => 'Monday-Friday with on-call rotation',
                    'hours' => '7:00 AM - 3:30 PM',
                    'on_call_required' => true,
                ],
                'experience_level' => 'mid',
                'min_experience_years' => 2,
                'benefits' => [
                    'Competitive salary',
                    'On-call and call-back pay',
                    'Health and dental insurance',
                    'Retirement plan with match',
                    'CNOR certification reimbursement',
                    'Perioperative training program',
                    'Continuing education support',
                ],
                'compliance_checklist' => [
                    'certifications' => ['BSN', 'RN', 'CNOR preferred', 'ACLS'],
                    'licenses' => ['State RN License', 'BLS'],
                    'requirements' => ['OR experience', 'Perioperative training'],
                    'shift_compliance' => ['On-call rotation required', 'Variable start times'],
                    'legal_requirements' => ['Equal Opportunity Employer', 'Health screening required'],
                ],
            ],

            // ALLIED HEALTH TEMPLATES
            [
                'name' => 'Physical Therapist Template',
                'category' => 'allied_health',
                'role_type' => 'Physical Therapist',
                'tags' => ['PT', 'rehabilitation', 'therapy', 'allied health'],
                'title' => 'Physical Therapist (PT)',
                'employment_type' => 'full-time',
                'work_mode' => 'on-site',
                'required_certifications' => ['DPT', 'PT License', 'BLS'],
                'required_licenses' => ['State Physical Therapy License'],
                'shift_type' => 'day',
                'shift_details' => [
                    'schedule' => 'Monday-Friday',
                    'hours' => '8:00 AM - 5:00 PM',
                    'weekend_required' => false,
                ],
                'experience_level' => 'entry',
                'min_experience_years' => 0,
                'benefits' => [
                    'Competitive salary',
                    'Health and dental insurance',
                    '401(k) with employer contribution',
                    'Continuing education allowance',
                    'Mentorship program for new grads',
                    'Professional development opportunities',
                    'Paid time off',
                ],
                'compliance_checklist' => [
                    'certifications' => ['DPT', 'BLS'],
                    'licenses' => ['State PT License'],
                    'requirements' => ['New graduates welcome', 'Clinical rotations'],
                    'shift_compliance' => ['Standard business hours', 'No weekends'],
                    'legal_requirements' => ['Equal Opportunity Employer', 'Background check'],
                ],
            ],

            [
                'name' => 'Respiratory Therapist Template',
                'category' => 'allied_health',
                'role_type' => 'Respiratory Therapist',
                'tags' => ['RT', 'respiratory', 'pulmonary', 'allied health'],
                'title' => 'Respiratory Therapist (RT)',
                'employment_type' => 'full-time',
                'work_mode' => 'on-site',
                'required_certifications' => ['RRT', 'CRT', 'BLS', 'NPS'],
                'required_licenses' => ['State Respiratory Therapy License'],
                'shift_type' => '12hr',
                'shift_details' => [
                    'schedule' => '3x12 hour shifts',
                    'hours' => 'Day or Night shift',
                    'emergency_call' => false,
                ],
                'experience_level' => 'entry',
                'min_experience_years' => 0,
                'benefits' => [
                    'Competitive salary',
                    'Shift differential pay',
                    'Comprehensive health benefits',
                    'Retirement plan',
                    'Credentialing support (RRT/CRT)',
                    'Continuing education',
                    'Career advancement opportunities',
                ],
                'compliance_checklist' => [
                    'certifications' => ['RRT or CRT', 'BLS', 'NPS preferred'],
                    'licenses' => ['State RT License'],
                    'requirements' => ['NBRC credential', 'Clinical experience'],
                    'shift_compliance' => ['12-hour shifts', 'Weekend rotation'],
                    'legal_requirements' => ['Equal Opportunity Employer', 'Drug screening'],
                ],
            ],

            // PHYSICIAN TEMPLATES
            [
                'name' => 'Emergency Medicine Physician Template',
                'category' => 'physician',
                'role_type' => 'Emergency Medicine Physician',
                'tags' => ['physician', 'MD', 'DO', 'emergency medicine', 'EM'],
                'title' => 'Emergency Medicine Physician',
                'employment_type' => 'full-time',
                'work_mode' => 'on-site',
                'required_certifications' => ['MD', 'DO', 'Board Certification', 'DEA License', 'ACLS', 'ATLS', 'PALS'],
                'required_licenses' => ['State Medical License', 'DEA Registration'],
                'shift_type' => '12hr',
                'shift_details' => [
                    'schedule' => 'Flexible scheduling',
                    'hours' => 'Varied shifts',
                    'democratic_scheduling' => true,
                ],
                'experience_level' => 'senior',
                'min_experience_years' => 3,
                'benefits' => [
                    'Highly competitive compensation',
                    'Productivity bonuses',
                    'Comprehensive malpractice insurance',
                    'Health, dental, vision insurance',
                    '401(k) with generous match',
                    'CME allowance',
                    'Paid licensing and certifications',
                    'Partnership track available',
                ],
                'compliance_checklist' => [
                    'certifications' => ['Board Certified EM', 'ACLS', 'ATLS', 'PALS', 'DEA'],
                    'licenses' => ['State Medical License', 'DEA Registration'],
                    'requirements' => ['ABEM or AOBEM certification', 'Residency in EM'],
                    'shift_compliance' => ['Democratic scheduling', 'Flexible shifts'],
                    'legal_requirements' => ['Equal Opportunity Employer', 'Credentialing required'],
                ],
            ],

            // HEALTHCARE SUPPORT TEMPLATES
            [
                'name' => 'Medical Assistant Template',
                'category' => 'healthcare_support',
                'role_type' => 'Medical Assistant',
                'tags' => ['MA', 'CMA', 'clinical', 'support', 'assistant'],
                'title' => 'Certified Medical Assistant (CMA)',
                'employment_type' => 'full-time',
                'work_mode' => 'on-site',
                'required_certifications' => ['CMA', 'RMA', 'BLS'],
                'required_licenses' => ['Certification (CMA or RMA)'],
                'shift_type' => 'day',
                'shift_details' => [
                    'schedule' => 'Monday-Friday',
                    'hours' => '8:00 AM - 5:00 PM',
                    'occasional_saturday' => true,
                ],
                'experience_level' => 'entry',
                'min_experience_years' => 0,
                'benefits' => [
                    'Competitive hourly wage',
                    'Health and dental insurance',
                    'Paid time off',
                    'Continuing education support',
                    'Career advancement opportunities',
                    'Professional certification reimbursement',
                    'Employee assistance program',
                ],
                'compliance_checklist' => [
                    'certifications' => ['CMA or RMA', 'BLS'],
                    'licenses' => ['Certification required'],
                    'requirements' => ['Completion of accredited MA program', 'New grads welcome'],
                    'shift_compliance' => ['Standard business hours', 'Occasional Saturday'],
                    'legal_requirements' => ['Equal Opportunity Employer', 'Background check', 'Immunizations'],
                ],
            ],
        ];
    }

    private function getErNurseDescription(): string
    {
        return json_encode([
            'title' => 'Emergency Room Nurse (RN) - Fast-Paced, Rewarding Environment',
            'summary' => 'Join our dynamic Emergency Department as an ER Nurse where you will provide critical care to patients in urgent and emergent conditions. We offer competitive compensation, excellent benefits, and opportunities for professional growth.',
            'responsibilities' => [
                'Perform rapid triage and assessment of patients presenting to the Emergency Department',
                'Administer medications, IV therapy, and life-saving interventions',
                'Collaborate with physicians and interdisciplinary team members',
                'Monitor and operate medical equipment (EKG, defibrillator, etc.)',
                'Provide patient and family education regarding treatment plans',
                'Maintain accurate and timely documentation in electronic health records',
                'Respond to code blue and trauma situations',
                'Mentor and orient new staff members',
            ],
            'qualifications' => [
                'Required: Associate or Bachelor of Science in Nursing (ASN/BSN)',
                'Required: Minimum 2 years of ER or critical care experience',
                'Preferred: CEN (Certified Emergency Nurse) certification',
                'Strong critical thinking and decision-making skills',
                'Excellent communication and interpersonal abilities',
                'Ability to work effectively in high-stress, fast-paced environment',
            ],
            'certifications' => ['BSN or ASN', 'Current RN License', 'ACLS', 'PALS', 'TNCC', 'BLS'],
            'shift_details' => '3x12 hour shifts per week, day or night shift available, weekend and holiday rotation required',
            'keywords' => ['emergency nurse', 'ER RN', 'trauma nurse', 'critical care', 'acute care', 'triage', 'emergency department'],
        ]);
    }

    private function getTravelRnDescription(): string
    {
        return json_encode([
            'title' => 'Travel Registered Nurse (RN) - 13-Week Assignments, Top Pay',
            'summary' => 'Exciting travel nursing opportunities across the country! Enjoy the freedom of flexible assignments while receiving industry-leading compensation and comprehensive benefits. Explore new locations while advancing your nursing career.',
            'responsibilities' => [
                'Deliver high-quality patient care in diverse clinical settings',
                'Quickly adapt to new facility protocols and electronic health record systems',
                'Collaborate with facility staff to ensure continuity of care',
                'Maintain compliance with state licensing and facility requirements',
                'Provide patient assessment, medication administration, and treatment planning',
                'Document patient care accurately and timely',
                'Participate in shift handoffs and interdisciplinary team meetings',
            ],
            'qualifications' => [
                'Required: Active RN license in state of assignment (Compact license preferred)',
                'Required: Minimum 1 year of recent experience in specialty',
                'Required: BSN from an accredited nursing program',
                'Flexibility and adaptability to new environments',
                'Strong clinical skills and critical thinking abilities',
                'Excellent communication and teamwork skills',
            ],
            'certifications' => ['Active RN License', 'BSN', 'ACLS', 'BLS', 'Specialty certifications as required'],
            'shift_details' => '13-week assignments with extension options, flexible scheduling, day/night shifts available',
            'keywords' => ['travel nurse', 'travel RN', 'contract nursing', 'temporary assignment', 'flexible nursing'],
        ]);
    }

    private function getIcuNurseDescription(): string
    {
        return json_encode([
            'title' => 'Intensive Care Unit (ICU) Nurse - Critical Care Excellence',
            'summary' => 'Join our award-winning Intensive Care Unit where you will provide specialized care to critically ill patients. Our ICU features state-of-the-art technology, supportive teamwork, and opportunities for advanced professional development.',
            'responsibilities' => [
                'Monitor and assess critically ill patients with complex medical conditions',
                'Administer vasoactive medications, ventilator management, and advanced interventions',
                'Operate and interpret advanced monitoring equipment',
                'Collaborate with intensivists and multidisciplinary care team',
                'Provide emotional support to patients and families during critical times',
                'Participate in quality improvement and evidence-based practice initiatives',
                'Maintain meticulous documentation of patient care activities',
                'Respond to rapid response and code situations',
            ],
            'qualifications' => [
                'Required: BSN from accredited nursing program',
                'Required: Minimum 2 years of ICU or critical care experience',
                'Preferred: CCRN certification',
                'Advanced hemodynamic monitoring skills',
                'Ventilator management experience',
                'Strong assessment and critical thinking skills',
                'Ability to remain calm under pressure',
            ],
            'certifications' => ['BSN', 'RN License', 'ACLS', 'BLS', 'CCRN preferred'],
            'shift_details' => '3x12 hour shifts, day or night shift options, weekend and holiday rotation',
            'keywords' => ['ICU nurse', 'critical care', 'intensive care', 'CCRN', 'ventilator management', 'hemodynamic monitoring'],
        ]);
    }
}
