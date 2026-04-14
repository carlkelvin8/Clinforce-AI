<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowAutomationSeeder extends Seeder
{
    public function run(): void
    {
        // Get a sample employer user
        $employerUser = DB::table('users')->where('role', 'employer')->first();
        if (!$employerUser) {
            $this->command->info('No employer users found. Skipping workflow automation seeding.');
            return;
        }

        // ═══════════════════════════════════════════════════════════
        // HIRING WORKFLOWS
        // ═══════════════════════════════════════════════════════════

        $workflowId = DB::table('hiring_workflows')->insertGetId([
            'employer_user_id' => $employerUser->id,
            'name' => 'Standard Nursing Workflow',
            'description' => 'Default workflow for nursing positions with comprehensive screening',
            'scope' => 'department',
            'department' => 'Nursing',
            'stages' => json_encode([
                ['name' => 'applied', 'label' => 'Application Received', 'sla_hours' => 24],
                ['name' => 'screening', 'label' => 'Initial Screening', 'sla_hours' => 48],
                ['name' => 'phone_screen', 'label' => 'Phone Interview', 'sla_hours' => 72],
                ['name' => 'interview', 'label' => 'In-Person Interview', 'sla_hours' => 96],
                ['name' => 'reference_check', 'label' => 'Reference Check', 'sla_hours' => 48],
                ['name' => 'background_check', 'label' => 'Background Check', 'sla_hours' => 120],
                ['name' => 'offer', 'label' => 'Offer Extended', 'sla_hours' => 24],
                ['name' => 'hired', 'label' => 'Hired', 'sla_hours' => 0],
            ]),
            'auto_advance_rules' => json_encode([
                'screening' => [
                    'next_stage' => 'phone_screen',
                    'require_all' => false,
                    'conditions' => [
                        ['type' => 'time_in_stage', 'hours' => 24],
                        ['type' => 'document_uploaded', 'document_type' => 'resume'],
                    ]
                ],
                'reference_check' => [
                    'next_stage' => 'background_check',
                    'require_all' => true,
                    'conditions' => [
                        ['type' => 'reference_received', 'count' => 2],
                    ]
                ]
            ]),
            'approval_rules' => json_encode([
                'offer' => [
                    'required' => true,
                    'approvers' => ['hiring_manager', 'hr_director'],
                ]
            ]),
            'sla_settings' => json_encode([
                'applied' => ['hours' => 24],
                'screening' => ['hours' => 48],
                'phone_screen' => ['hours' => 72],
                'interview' => ['hours' => 96],
                'reference_check' => ['hours' => 48],
                'background_check' => ['hours' => 120],
                'offer' => ['hours' => 24],
            ]),
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Emergency Department Workflow
        DB::table('hiring_workflows')->insert([
            'employer_user_id' => $employerUser->id,
            'name' => 'Emergency Department Fast Track',
            'description' => 'Expedited workflow for urgent ED positions',
            'scope' => 'department',
            'department' => 'Emergency',
            'stages' => json_encode([
                ['name' => 'applied', 'label' => 'Application Received', 'sla_hours' => 12],
                ['name' => 'screening', 'label' => 'Rapid Screening', 'sla_hours' => 24],
                ['name' => 'interview', 'label' => 'Interview', 'sla_hours' => 48],
                ['name' => 'offer', 'label' => 'Offer Extended', 'sla_hours' => 12],
                ['name' => 'hired', 'label' => 'Hired', 'sla_hours' => 0],
            ]),
            'auto_advance_rules' => json_encode([]),
            'approval_rules' => json_encode([]),
            'sla_settings' => json_encode([
                'applied' => ['hours' => 12],
                'screening' => ['hours' => 24],
                'interview' => ['hours' => 48],
                'offer' => ['hours' => 12],
            ]),
            'is_active' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ═══════════════════════════════════════════════════════════
        // EMAIL SEQUENCES
        // ═══════════════════════════════════════════════════════════

        // Welcome Sequence for New Candidates
        $welcomeSequenceId = DB::table('email_sequences')->insertGetId([
            'employer_user_id' => $employerUser->id,
            'name' => 'New Candidate Welcome Series',
            'description' => 'Welcome new candidates and guide them through the application process',
            'type' => 'welcome',
            'trigger_event' => 'application_submitted',
            'trigger_conditions' => json_encode([
                'first_application' => true,
            ]),
            'target_audience' => json_encode([
                'roles' => ['applicant'],
            ]),
            'is_active' => true,
            'total_emails' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Welcome sequence steps
        DB::table('email_sequence_steps')->insert([
            [
                'sequence_id' => $welcomeSequenceId,
                'step_number' => 1,
                'subject' => 'Welcome to {{company_name}} - Application Received!',
                'body_template' => "Hi {{user_name}},\n\nThank you for applying to {{company_name}}! We've received your application and our team is reviewing it.\n\nWhat happens next:\n• Our hiring team will review your application within 24-48 hours\n• If you're a good fit, we'll reach out to schedule a phone screening\n• You can track your application status in your dashboard\n\nWe're excited about the possibility of you joining our team!\n\nBest regards,\nThe {{company_name}} Hiring Team",
                'delay_hours' => 0,
                'send_conditions' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sequence_id' => $welcomeSequenceId,
                'step_number' => 2,
                'subject' => 'Tips for Success at {{company_name}}',
                'body_template' => "Hi {{user_name}},\n\nWhile we're reviewing your application, here are some tips to help you prepare:\n\n• Research our mission and values on our website\n• Review the job description and think of specific examples from your experience\n• Prepare questions about the role and our organization\n• Make sure your references are aware they may be contacted\n\nWe believe in transparency and want you to feel prepared for every step of our process.\n\nGood luck!\nThe {{company_name}} Team",
                'delay_hours' => 48,
                'send_conditions' => json_encode([
                    'application_status' => ['applied', 'screening'],
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sequence_id' => $welcomeSequenceId,
                'step_number' => 3,
                'subject' => 'Application Status Update',
                'body_template' => "Hi {{user_name}},\n\nJust wanted to give you a quick update on your application status.\n\nWe're still in the review process and appreciate your patience. If you have any questions or want to add additional information to your application, please don't hesitate to reach out.\n\nYou can always check your current status by logging into your candidate dashboard.\n\nThank you for your interest in {{company_name}}!\n\nBest,\nHiring Team",
                'delay_hours' => 120,
                'send_conditions' => json_encode([
                    'application_status' => ['applied', 'screening'],
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Interview Follow-up Sequence
        $followupSequenceId = DB::table('email_sequences')->insertGetId([
            'employer_user_id' => $employerUser->id,
            'name' => 'Post-Interview Follow-up',
            'description' => 'Follow up with candidates after interviews',
            'type' => 'follow_up',
            'trigger_event' => 'interview_completed',
            'trigger_conditions' => null,
            'target_audience' => json_encode([
                'roles' => ['applicant'],
            ]),
            'is_active' => true,
            'total_emails' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('email_sequence_steps')->insert([
            [
                'sequence_id' => $followupSequenceId,
                'step_number' => 1,
                'subject' => 'Thank you for interviewing with {{company_name}}',
                'body_template' => "Hi {{user_name}},\n\nThank you for taking the time to interview with us today. It was great getting to know you better and learning about your experience.\n\nNext steps:\n• We'll be making our decision within the next 3-5 business days\n• We'll contact you regardless of the outcome\n• If you have any follow-up questions, please don't hesitate to reach out\n\nWe appreciate your interest in joining our team at {{company_name}}.\n\nBest regards,\n{{interviewer_name}}",
                'delay_hours' => 2,
                'send_conditions' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sequence_id' => $followupSequenceId,
                'step_number' => 2,
                'subject' => 'Interview Decision Timeline Update',
                'body_template' => "Hi {{user_name}},\n\nI wanted to provide you with a quick update on our interview process.\n\nWe're still in the decision-making phase and want to ensure we're thorough in our evaluation. We expect to have an update for you by {{decision_date}}.\n\nThank you for your continued patience and interest in {{company_name}}.\n\nBest,\nHiring Team",
                'delay_hours' => 96,
                'send_conditions' => json_encode([
                    'application_status' => ['interview'],
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ═══════════════════════════════════════════════════════════
        // DOCUMENT TEMPLATES
        // ═══════════════════════════════════════════════════════════

        // Offer Letter Template
        DB::table('document_templates')->insert([
            'employer_user_id' => $employerUser->id,
            'name' => 'Standard Nursing Offer Letter',
            'description' => 'Standard offer letter template for nursing positions',
            'type' => 'offer_letter',
            'template_content' => "{{current_date}}\n\nDear {{candidate_name}},\n\nWe are pleased to offer you the position of {{job_title}} at {{company_name}}. We believe your skills and experience will be a valuable addition to our team.\n\nPosition Details:\n• Job Title: {{job_title}}\n• Department: {{department}}\n• Start Date: {{start_date}}\n• Salary: {{salary}} per year\n• Benefits: {{benefits}}\n• Reporting Manager: {{manager_name}}\n\nThis offer is contingent upon:\n• Successful completion of background check\n• Verification of professional licenses\n• Completion of pre-employment health screening\n\nPlease confirm your acceptance of this offer by {{response_deadline}}. If you have any questions, please don't hesitate to contact me.\n\nWe look forward to welcoming you to the {{company_name}} team!\n\nSincerely,\n\n{{hiring_manager_name}}\n{{hiring_manager_title}}\n{{company_name}}",
            'required_fields' => json_encode([
                'candidate_name', 'job_title', 'company_name', 'department', 
                'start_date', 'salary', 'manager_name', 'hiring_manager_name', 
                'hiring_manager_title', 'response_deadline'
            ]),
            'optional_fields' => json_encode([
                'benefits', 'additional_notes'
            ]),
            'file_format' => 'pdf',
            'is_default' => true,
            'is_active' => true,
            'letterhead_url' => null,
            'styling_options' => json_encode([
                'font_family' => 'Arial',
                'font_size' => 12,
                'line_height' => 1.5,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Employment Contract Template
        DB::table('document_templates')->insert([
            'employer_user_id' => $employerUser->id,
            'name' => 'Full-Time Employment Contract',
            'description' => 'Comprehensive employment contract for full-time positions',
            'type' => 'employment_contract',
            'template_content' => "EMPLOYMENT AGREEMENT\n\nThis Employment Agreement (\"Agreement\") is entered into on {{contract_date}} between {{company_name}} (\"Company\") and {{employee_name}} (\"Employee\").\n\n1. POSITION AND DUTIES\nEmployee is hired as {{job_title}} in the {{department}} department. Employee agrees to perform duties as assigned by the Company.\n\n2. COMPENSATION\nEmployee will receive an annual salary of {{annual_salary}}, paid in accordance with Company's standard payroll practices.\n\n3. BENEFITS\nEmployee is eligible for Company benefits including:\n{{benefits_list}}\n\n4. EMPLOYMENT TERM\nThis is an at-will employment relationship. Either party may terminate this agreement at any time with or without cause.\n\n5. CONFIDENTIALITY\nEmployee agrees to maintain confidentiality of all Company proprietary information.\n\n6. START DATE\nEmployee's start date is {{start_date}}.\n\nBy signing below, both parties agree to the terms of this Agreement.\n\nCOMPANY:\n{{company_name}}\n\nBy: _________________________\n{{hiring_manager_name}}\n{{hiring_manager_title}}\nDate: _______________________\n\nEMPLOYEE:\n\n_____________________________\n{{employee_name}}\nDate: _______________________",
            'required_fields' => json_encode([
                'contract_date', 'company_name', 'employee_name', 'job_title', 
                'department', 'annual_salary', 'start_date', 'hiring_manager_name', 
                'hiring_manager_title'
            ]),
            'optional_fields' => json_encode([
                'benefits_list', 'additional_clauses'
            ]),
            'file_format' => 'pdf',
            'is_default' => false,
            'is_active' => true,
            'letterhead_url' => null,
            'styling_options' => json_encode([
                'font_family' => 'Times New Roman',
                'font_size' => 11,
                'line_height' => 1.4,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Reference Letter Template
        DB::table('document_templates')->insert([
            'employer_user_id' => $employerUser->id,
            'name' => 'Professional Reference Letter',
            'description' => 'Template for providing professional references',
            'type' => 'reference_letter',
            'template_content' => "{{current_date}}\n\nTo Whom It May Concern:\n\nI am writing to provide a professional reference for {{candidate_name}}, who worked at {{company_name}} as {{former_position}} from {{employment_start}} to {{employment_end}}.\n\nDuring {{candidate_name}}'s tenure with our organization, they consistently demonstrated:\n• {{skill_1}}\n• {{skill_2}}\n• {{skill_3}}\n\n{{candidate_name}} was responsible for {{job_responsibilities}} and consistently met or exceeded expectations. They were a reliable team member who {{performance_highlights}}.\n\nI would highly recommend {{candidate_name}} for {{recommendation_type}} positions. They would be a valuable addition to any healthcare team.\n\nPlease feel free to contact me if you need any additional information.\n\nSincerely,\n\n{{reference_name}}\n{{reference_title}}\n{{company_name}}\n{{contact_phone}}\n{{contact_email}}",
            'required_fields' => json_encode([
                'candidate_name', 'company_name', 'former_position', 'employment_start', 
                'employment_end', 'reference_name', 'reference_title', 'contact_phone', 
                'contact_email'
            ]),
            'optional_fields' => json_encode([
                'skill_1', 'skill_2', 'skill_3', 'job_responsibilities', 
                'performance_highlights', 'recommendation_type'
            ]),
            'file_format' => 'pdf',
            'is_default' => false,
            'is_active' => true,
            'letterhead_url' => null,
            'styling_options' => json_encode([
                'font_family' => 'Arial',
                'font_size' => 12,
                'line_height' => 1.5,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Workflow automation sample data seeded successfully!');
    }
}