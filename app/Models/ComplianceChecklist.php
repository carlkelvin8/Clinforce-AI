<?php

namespace App\Models;

/**
 * ComplianceChecklist - Helper class for job template compliance
 * 
 * This is a value object/helper that works with the compliance_checklist
 * JSON field in the job_templates table. It provides structured access
 * to compliance requirements for healthcare job postings.
 */
class ComplianceChecklist
{
    public array $certifications = [];
    public array $licenses = [];
    public array $requirements = [];
    public ?string $shiftType = null;
    public array $shiftDetails = [];
    public ?string $experienceLevel = null;
    public ?int $minExperienceYears = null;
    public array $benefits = [];

    public static function make(array $data = []): self
    {
        $instance = new self();
        
        $instance->certifications = $data['certifications'] ?? [];
        $instance->licenses = $data['licenses'] ?? [];
        $instance->requirements = $data['requirements'] ?? [];
        $instance->shiftType = $data['shift_type'] ?? null;
        $instance->shiftDetails = $data['shift_details'] ?? [];
        $instance->experienceLevel = $data['experience_level'] ?? null;
        $instance->minExperienceYears = $data['min_experience_years'] ?? null;
        $instance->benefits = $data['benefits'] ?? [];
        
        return $instance;
    }

    public function toArray(): array
    {
        return [
            'certifications' => $this->certifications,
            'licenses' => $this->licenses,
            'requirements' => $this->requirements,
            'shift_type' => $this->shiftType,
            'shift_details' => $this->shiftDetails,
            'experience_level' => $this->experienceLevel,
            'min_experience_years' => $this->minExperienceYears,
            'benefits' => $this->benefits,
        ];
    }

    public function isComplete(): bool
    {
        return !empty($this->certifications) 
            && !empty($this->licenses)
            && $this->shiftType !== null
            && $this->experienceLevel !== null;
    }

    public function getMissingItems(): array
    {
        $missing = [];
        
        if (empty($this->certifications)) {
            $missing[] = 'certifications';
        }
        if (empty($this->licenses)) {
            $missing[] = 'licenses';
        }
        if ($this->shiftType === null) {
            $missing[] = 'shift_type';
        }
        if ($this->experienceLevel === null) {
            $missing[] = 'experience_level';
        }
        
        return $missing;
    }

    /**
     * Get common healthcare certifications for job templates
     */
    public static function getCommonCertifications(string $roleType): array
    {
        $certifications = [
            'ER Nurse' => ['BSN', 'RN', 'ACLS', 'PALS', 'TNCC', 'CEN'],
            'Travel RN' => ['BSN', 'RN', 'ACLS', 'BLS'],
            'ICU Nurse' => ['BSN', 'RN', 'ACLS', 'CCRN'],
            'OR Nurse' => ['BSN', 'RN', 'CNOR', 'ACLS'],
            'Pediatric Nurse' => ['BSN', 'RN', 'PALS', 'CPN'],
            'Labor & Delivery' => ['BSN', 'RN', 'NRP', 'AWHONN'],
            'Physician' => ['MD', 'DO', 'Board Certification', 'DEA License'],
            'Physical Therapist' => ['DPT', 'PT License', 'BLS'],
            'Respiratory Therapist' => ['RRT', 'CRT', 'BLS', 'NPS'],
            'Medical Assistant' => ['CMA', 'RMA', 'BLS'],
        ];

        return $certifications[$roleType] ?? ['RN', 'BLS'];
    }

    /**
     * Get common shift types for healthcare jobs
     */
    public static function getShiftTypes(): array
    {
        return [
            'day' => ['label' => 'Day Shift', 'hours' => '7AM-7PM'],
            'night' => ['label' => 'Night Shift', 'hours' => '7PM-7AM'],
            'rotating' => ['label' => 'Rotating Shift', 'hours' => 'Varies'],
            '12hr' => ['label' => '12-Hour Shift', 'hours' => '12 hours'],
            '8hr' => ['label' => '8-Hour Shift', 'hours' => '8 hours'],
            'prn' => ['label' => 'PRN/As Needed', 'hours' => 'Flexible'],
        ];
    }

    /**
     * Get experience level labels
     */
    public static function getExperienceLevels(): array
    {
        return [
            'entry' => ['label' => 'Entry Level', 'years' => '0-2'],
            'mid' => ['label' => 'Mid Level', 'years' => '2-5'],
            'senior' => ['label' => 'Senior Level', 'years' => '5-10'],
            'expert' => ['label' => 'Expert Level', 'years' => '10+'],
        ];
    }
}
