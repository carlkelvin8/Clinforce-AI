<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssessmentTemplate;

class AssessmentTemplatesSeeder extends Seeder
{
    public function run()
    {
        $templates = [
            [
                'title' => 'Basic Nursing Fundamentals',
                'slug' => 'basic-nursing-fundamentals',
                'description' => 'Test your knowledge of basic nursing principles, vital signs, and patient care fundamentals.',
                'skill_name' => 'Nursing Fundamentals',
                'category' => 'Healthcare',
                'difficulty' => 'Beginner',
                'duration_minutes' => 30,
                'passing_score' => 70,
                'is_active' => true,
                'questions' => [
                    [
                        'id' => 'q1',
                        'question' => 'What is the normal range for adult heart rate?',
                        'type' => 'multiple_choice',
                        'options' => ['40-60 bpm', '60-100 bpm', '100-120 bpm', '120-140 bpm'],
                        'correct_answer' => '60-100 bpm',
                        'category' => 'Vital Signs'
                    ],
                    [
                        'id' => 'q2',
                        'question' => 'Which position is best for a patient with difficulty breathing?',
                        'type' => 'multiple_choice',
                        'options' => ['Supine', 'Prone', 'Fowler\'s', 'Trendelenburg'],
                        'correct_answer' => 'Fowler\'s',
                        'category' => 'Patient Positioning'
                    ],
                    [
                        'id' => 'q3',
                        'question' => 'What is the first step in hand hygiene?',
                        'type' => 'multiple_choice',
                        'options' => ['Apply soap', 'Turn on water', 'Remove jewelry', 'Wet hands'],
                        'correct_answer' => 'Remove jewelry',
                        'category' => 'Infection Control'
                    ],
                    [
                        'id' => 'q4',
                        'question' => 'Normal blood pressure for adults is:',
                        'type' => 'multiple_choice',
                        'options' => ['Less than 120/80 mmHg', '120-139/80-89 mmHg', '140-159/90-99 mmHg', 'Above 160/100 mmHg'],
                        'correct_answer' => 'Less than 120/80 mmHg',
                        'category' => 'Vital Signs'
                    ],
                    [
                        'id' => 'q5',
                        'question' => 'Which of the following is a sign of infection?',
                        'type' => 'multiple_choice',
                        'options' => ['Decreased temperature', 'Bradycardia', 'Elevated white blood cell count', 'Decreased respiratory rate'],
                        'correct_answer' => 'Elevated white blood cell count',
                        'category' => 'Assessment'
                    ]
                ]
            ],
            [
                'title' => 'Advanced Cardiac Care',
                'slug' => 'advanced-cardiac-care',
                'description' => 'Advanced assessment covering cardiac emergencies, ECG interpretation, and critical care protocols.',
                'skill_name' => 'Cardiac Care',
                'category' => 'Healthcare',
                'difficulty' => 'Advanced',
                'duration_minutes' => 45,
                'passing_score' => 80,
                'is_active' => true,
                'questions' => [
                    [
                        'id' => 'q1',
                        'question' => 'What is the first-line treatment for ventricular fibrillation?',
                        'type' => 'multiple_choice',
                        'options' => ['Epinephrine', 'Defibrillation', 'Atropine', 'Amiodarone'],
                        'correct_answer' => 'Defibrillation',
                        'category' => 'Emergency Care'
                    ],
                    [
                        'id' => 'q2',
                        'question' => 'Which ECG finding indicates a STEMI?',
                        'type' => 'multiple_choice',
                        'options' => ['ST depression', 'ST elevation', 'T wave inversion', 'Q waves'],
                        'correct_answer' => 'ST elevation',
                        'category' => 'ECG Interpretation'
                    ],
                    [
                        'id' => 'q3',
                        'question' => 'What is the target door-to-balloon time for STEMI patients?',
                        'type' => 'multiple_choice',
                        'options' => ['60 minutes', '90 minutes', '120 minutes', '180 minutes'],
                        'correct_answer' => '90 minutes',
                        'category' => 'Quality Metrics'
                    ],
                    [
                        'id' => 'q4',
                        'question' => 'Which medication is contraindicated in cardiogenic shock?',
                        'type' => 'multiple_choice',
                        'options' => ['Dopamine', 'Dobutamine', 'Beta-blockers', 'Norepinephrine'],
                        'correct_answer' => 'Beta-blockers',
                        'category' => 'Pharmacology'
                    ],
                    [
                        'id' => 'q5',
                        'question' => 'What is the normal ejection fraction range?',
                        'type' => 'multiple_choice',
                        'options' => ['40-50%', '50-70%', '70-80%', '80-90%'],
                        'correct_answer' => '50-70%',
                        'category' => 'Cardiac Function'
                    ]
                ]
            ],
            [
                'title' => 'Medical Terminology Basics',
                'slug' => 'medical-terminology-basics',
                'description' => 'Essential medical terminology including prefixes, suffixes, and common medical terms.',
                'skill_name' => 'Medical Terminology',
                'category' => 'Medical',
                'difficulty' => 'Beginner',
                'duration_minutes' => 25,
                'passing_score' => 75,
                'is_active' => true,
                'questions' => [
                    [
                        'id' => 'q1',
                        'question' => 'What does the prefix "hyper-" mean?',
                        'type' => 'multiple_choice',
                        'options' => ['Below normal', 'Above normal', 'Within normal', 'Without'],
                        'correct_answer' => 'Above normal',
                        'category' => 'Prefixes'
                    ],
                    [
                        'id' => 'q2',
                        'question' => 'The suffix "-itis" refers to:',
                        'type' => 'multiple_choice',
                        'options' => ['Inflammation', 'Removal', 'Study of', 'Condition'],
                        'correct_answer' => 'Inflammation',
                        'category' => 'Suffixes'
                    ],
                    [
                        'id' => 'q3',
                        'question' => 'What does "bradycardia" mean?',
                        'type' => 'multiple_choice',
                        'options' => ['Fast heart rate', 'Slow heart rate', 'Irregular heart rate', 'Normal heart rate'],
                        'correct_answer' => 'Slow heart rate',
                        'category' => 'Cardiovascular Terms'
                    ],
                    [
                        'id' => 'q4',
                        'question' => 'The term "anterior" means:',
                        'type' => 'multiple_choice',
                        'options' => ['Back', 'Front', 'Side', 'Middle'],
                        'correct_answer' => 'Front',
                        'category' => 'Anatomical Position'
                    ],
                    [
                        'id' => 'q5',
                        'question' => 'What does "dyspnea" refer to?',
                        'type' => 'multiple_choice',
                        'options' => ['Difficulty swallowing', 'Difficulty breathing', 'Difficulty speaking', 'Difficulty hearing'],
                        'correct_answer' => 'Difficulty breathing',
                        'category' => 'Respiratory Terms'
                    ]
                ]
            ],
            [
                'title' => 'Pharmacology Fundamentals',
                'slug' => 'pharmacology-fundamentals',
                'description' => 'Core pharmacology concepts including drug interactions, dosages, and therapeutic ranges.',
                'skill_name' => 'Pharmacology',
                'category' => 'Medical',
                'difficulty' => 'Intermediate',
                'duration_minutes' => 40,
                'passing_score' => 75,
                'is_active' => true,
                'questions' => [
                    [
                        'id' => 'q1',
                        'question' => 'What is the therapeutic range for digoxin?',
                        'type' => 'multiple_choice',
                        'options' => ['0.5-2.0 ng/mL', '1.0-2.5 ng/mL', '2.0-4.0 ng/mL', '4.0-6.0 ng/mL'],
                        'correct_answer' => '0.5-2.0 ng/mL',
                        'category' => 'Drug Levels'
                    ],
                    [
                        'id' => 'q2',
                        'question' => 'Which route provides the fastest drug absorption?',
                        'type' => 'multiple_choice',
                        'options' => ['Oral', 'Intramuscular', 'Subcutaneous', 'Intravenous'],
                        'correct_answer' => 'Intravenous',
                        'category' => 'Pharmacokinetics'
                    ],
                    [
                        'id' => 'q3',
                        'question' => 'What is the antidote for warfarin overdose?',
                        'type' => 'multiple_choice',
                        'options' => ['Protamine sulfate', 'Vitamin K', 'Naloxone', 'Flumazenil'],
                        'correct_answer' => 'Vitamin K',
                        'category' => 'Antidotes'
                    ],
                    [
                        'id' => 'q4',
                        'question' => 'Which medication requires monitoring of liver function?',
                        'type' => 'multiple_choice',
                        'options' => ['Acetaminophen', 'Ibuprofen', 'Aspirin', 'All of the above'],
                        'correct_answer' => 'All of the above',
                        'category' => 'Drug Monitoring'
                    ],
                    [
                        'id' => 'q5',
                        'question' => 'What does "PRN" mean in medication orders?',
                        'type' => 'multiple_choice',
                        'options' => ['As needed', 'Before meals', 'After meals', 'At bedtime'],
                        'correct_answer' => 'As needed',
                        'category' => 'Medication Orders'
                    ]
                ]
            ],
            [
                'title' => 'Healthcare Administration',
                'slug' => 'healthcare-administration',
                'description' => 'Healthcare administration principles including HIPAA, coding systems, and quality management.',
                'skill_name' => 'Healthcare Administration',
                'category' => 'Administrative',
                'difficulty' => 'Intermediate',
                'duration_minutes' => 35,
                'passing_score' => 70,
                'is_active' => true,
                'questions' => [
                    [
                        'id' => 'q1',
                        'question' => 'What does HIPAA stand for?',
                        'type' => 'multiple_choice',
                        'options' => ['Health Insurance Portability and Accountability Act', 'Healthcare Information Privacy and Access Act', 'Health Information Protection and Audit Act', 'Healthcare Insurance Policy and Administration Act'],
                        'correct_answer' => 'Health Insurance Portability and Accountability Act',
                        'category' => 'Regulations'
                    ],
                    [
                        'id' => 'q2',
                        'question' => 'Which coding system is used for medical procedures?',
                        'type' => 'multiple_choice',
                        'options' => ['ICD-10', 'CPT', 'HCPCS', 'DRG'],
                        'correct_answer' => 'CPT',
                        'category' => 'Medical Coding'
                    ],
                    [
                        'id' => 'q3',
                        'question' => 'What is the purpose of quality assurance in healthcare?',
                        'type' => 'multiple_choice',
                        'options' => ['Cost reduction', 'Patient safety', 'Staff efficiency', 'All of the above'],
                        'correct_answer' => 'All of the above',
                        'category' => 'Quality Management'
                    ],
                    [
                        'id' => 'q4',
                        'question' => 'Which document authorizes treatment decisions when a patient cannot?',
                        'type' => 'multiple_choice',
                        'options' => ['Living will', 'Power of attorney', 'Advance directive', 'All of the above'],
                        'correct_answer' => 'All of the above',
                        'category' => 'Legal Documents'
                    ],
                    [
                        'id' => 'q5',
                        'question' => 'What is the primary goal of case management?',
                        'type' => 'multiple_choice',
                        'options' => ['Cost containment', 'Care coordination', 'Documentation', 'Billing accuracy'],
                        'correct_answer' => 'Care coordination',
                        'category' => 'Case Management'
                    ]
                ]
            ],
            [
                'title' => 'Clinical Laboratory Basics',
                'slug' => 'clinical-laboratory-basics',
                'description' => 'Laboratory medicine fundamentals including specimen collection, normal values, and quality control.',
                'skill_name' => 'Laboratory Medicine',
                'category' => 'Technical',
                'difficulty' => 'Intermediate',
                'duration_minutes' => 30,
                'passing_score' => 75,
                'is_active' => true,
                'questions' => [
                    [
                        'id' => 'q1',
                        'question' => 'What is the normal range for hemoglobin in adult males?',
                        'type' => 'multiple_choice',
                        'options' => ['10-12 g/dL', '12-14 g/dL', '14-18 g/dL', '18-20 g/dL'],
                        'correct_answer' => '14-18 g/dL',
                        'category' => 'Hematology'
                    ],
                    [
                        'id' => 'q2',
                        'question' => 'Which tube is used for glucose testing?',
                        'type' => 'multiple_choice',
                        'options' => ['Red top', 'Purple top', 'Gray top', 'Blue top'],
                        'correct_answer' => 'Gray top',
                        'category' => 'Specimen Collection'
                    ],
                    [
                        'id' => 'q3',
                        'question' => 'What does an elevated troponin level indicate?',
                        'type' => 'multiple_choice',
                        'options' => ['Kidney damage', 'Liver damage', 'Heart damage', 'Lung damage'],
                        'correct_answer' => 'Heart damage',
                        'category' => 'Cardiac Markers'
                    ],
                    [
                        'id' => 'q4',
                        'question' => 'Normal fasting glucose level is:',
                        'type' => 'multiple_choice',
                        'options' => ['Less than 100 mg/dL', '100-125 mg/dL', '126-199 mg/dL', 'Greater than 200 mg/dL'],
                        'correct_answer' => 'Less than 100 mg/dL',
                        'category' => 'Chemistry'
                    ],
                    [
                        'id' => 'q5',
                        'question' => 'What is the most common cause of hemolysis in blood samples?',
                        'type' => 'multiple_choice',
                        'options' => ['Improper storage', 'Rough handling', 'Wrong tube type', 'Delayed processing'],
                        'correct_answer' => 'Rough handling',
                        'category' => 'Quality Control'
                    ]
                ]
            ]
        ];

        foreach ($templates as $templateData) {
            AssessmentTemplate::create($templateData);
        }
    }
}