import { test, expect } from '@playwright/test';

// Test configuration
const BASE_URL = 'http://localhost:8000';
const API_BASE = `${BASE_URL}/api`;

// Test user credentials
const EMPLOYER_CREDENTIALS = {
  identifier: 'employer@aiclinforce.com',
  password: 'Employer@Staging2026!'
};

let authToken = '';
let userId = '';

test.describe('Learning & Development System Tests', () => {
  
  test.beforeAll(async ({ request }) => {
    // Login and get auth token
    const loginResponse = await request.post(`${API_BASE}/auth/login`, {
      data: EMPLOYER_CREDENTIALS
    });
    
    console.log('Login response status:', loginResponse.status());
    const loginData = await loginResponse.json();
    console.log('Login response data:', JSON.stringify(loginData, null, 2));
    
    if (!loginResponse.ok()) {
      throw new Error(`Login failed: ${loginResponse.status()} - ${JSON.stringify(loginData)}`);
    }
    
    authToken = loginData.data.token;
    userId = loginData.data.user.id;
    
    console.log('✅ Authentication successful');
  });

  // ═══════════════════════════════════════════════════════════
  // LEARNING DEVELOPMENT CONTROLLER TESTS
  // ═══════════════════════════════════════════════════════════

  test.describe('Learning Development Dashboard', () => {
    
    test('should get learning dashboard data', async ({ request }) => {
      const response = await request.get(`${API_BASE}/learning-development/dashboard`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(data.data).toHaveProperty('overview');
      expect(data.data).toHaveProperty('skill_gaps');
      expect(data.data).toHaveProperty('recommendations');
      expect(data.data).toHaveProperty('progress');
      expect(data.data).toHaveProperty('certifications');
      expect(data.data).toHaveProperty('mentorship');
      
      console.log('✅ Dashboard data loaded successfully');
    });
  });

  test.describe('Skills Management', () => {
    
    test('should get skills catalog', async ({ request }) => {
      const response = await request.get(`${API_BASE}/learning-development/skills-catalog`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(Array.isArray(data.data)).toBeTruthy();
      
      if (data.data.length > 0) {
        const skill = data.data[0];
        expect(skill).toHaveProperty('id');
        expect(skill).toHaveProperty('name');
        expect(skill).toHaveProperty('category');
        expect(skill).toHaveProperty('description');
      }
      
      console.log(`✅ Skills catalog loaded: ${data.data.length} skills`);
    });

    test('should get user skills', async ({ request }) => {
      const response = await request.get(`${API_BASE}/learning-development/user-skills`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(Array.isArray(data.data)).toBeTruthy();
      
      console.log(`✅ User skills loaded: ${data.data.length} skills`);
    });

    test('should add a new user skill', async ({ request }) => {
      // First get a skill from catalog
      const catalogResponse = await request.get(`${API_BASE}/learning-development/skills-catalog`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      const catalogData = await catalogResponse.json();
      if (catalogData.data.length === 0) {
        console.log('⚠️ No skills in catalog to test with');
        return;
      }
      
      const skillId = catalogData.data[0].id;
      
      const response = await request.post(`${API_BASE}/learning-development/user-skills`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        data: {
          skill_id: skillId,
          proficiency_level: 'intermediate',
          years_experience: 3,
          notes: 'Test skill added via Playwright',
          is_featured: true
        }
      });
      
      if (response.status() === 400) {
        // Skill might already exist
        const errorData = await response.json();
        if (errorData.message && errorData.message.includes('already exists')) {
          console.log('✅ Skill already exists (expected behavior)');
          return;
        }
      }
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(data.data).toHaveProperty('id');
      
      console.log('✅ User skill added successfully');
    });
  });

  test.describe('Skill Gap Analysis', () => {
    
    test('should analyze skill gaps', async ({ request }) => {
      const response = await request.post(`${API_BASE}/learning-development/analyze-skill-gaps`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        data: {
          target_role_title: 'Senior Nurse Manager'
        }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(data.data).toHaveProperty('gaps');
      expect(data.data).toHaveProperty('total_gaps');
      expect(data.data).toHaveProperty('critical_gaps');
      expect(data.data).toHaveProperty('recommendations');
      
      console.log(`✅ Skill gap analysis completed: ${data.data.total_gaps} gaps found`);
    });

    test('should get existing skill gaps', async ({ request }) => {
      const response = await request.get(`${API_BASE}/learning-development/skill-gaps`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(Array.isArray(data.data)).toBeTruthy();
      
      console.log(`✅ Skill gaps retrieved: ${data.data.length} gaps`);
    });
  });

  test.describe('Learning Courses', () => {
    
    test('should get learning courses', async ({ request }) => {
      const response = await request.get(`${API_BASE}/learning-development/courses`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      
      if (data.data.data && data.data.data.length > 0) {
        const course = data.data.data[0];
        expect(course).toHaveProperty('id');
        expect(course).toHaveProperty('title');
        expect(course).toHaveProperty('description');
        expect(course).toHaveProperty('provider_name');
      }
      
      console.log('✅ Learning courses loaded successfully');
    });

    test('should get course details', async ({ request }) => {
      // First get courses to find a valid course ID
      const coursesResponse = await request.get(`${API_BASE}/learning-development/courses`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      const coursesData = await coursesResponse.json();
      if (!coursesData.data.data || coursesData.data.data.length === 0) {
        console.log('⚠️ No courses available to test with');
        return;
      }
      
      const courseId = coursesData.data.data[0].id;
      
      const response = await request.get(`${API_BASE}/learning-development/courses/${courseId}`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(data.data).toHaveProperty('id');
      expect(data.data).toHaveProperty('title');
      
      console.log('✅ Course details retrieved successfully');
    });

    test('should enroll in a course', async ({ request }) => {
      // First get courses
      const coursesResponse = await request.get(`${API_BASE}/learning-development/courses`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      const coursesData = await coursesResponse.json();
      if (!coursesData.data.data || coursesData.data.data.length === 0) {
        console.log('⚠️ No courses available to test enrollment');
        return;
      }
      
      const courseId = coursesData.data.data[0].id;
      
      const response = await request.post(`${API_BASE}/learning-development/courses/${courseId}/enroll`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        data: {
          payment_status: 'free'
        }
      });
      
      if (response.status() === 400) {
        // Might already be enrolled
        const errorData = await response.json();
        if (errorData.message && errorData.message.includes('Already enrolled')) {
          console.log('✅ Already enrolled in course (expected behavior)');
          return;
        }
      }
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(data.data).toHaveProperty('enrollment_id');
      
      console.log('✅ Course enrollment successful');
    });
  });

  test.describe('Learning Recommendations', () => {
    
    test('should get learning recommendations', async ({ request }) => {
      const response = await request.get(`${API_BASE}/learning-development/recommendations`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(Array.isArray(data.data)).toBeTruthy();
      
      console.log(`✅ Learning recommendations retrieved: ${data.data.length} recommendations`);
    });

    test('should generate new recommendations', async ({ request }) => {
      const response = await request.post(`${API_BASE}/learning-development/generate-recommendations`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(data.data).toHaveProperty('generated_count');
      
      console.log(`✅ Generated ${data.data.generated_count} new recommendations`);
    });
  });

  // ═══════════════════════════════════════════════════════════
  // MENTORSHIP CONTROLLER TESTS
  // ═══════════════════════════════════════════════════════════

  test.describe('Mentorship Program', () => {
    
    test('should get mentor profile', async ({ request }) => {
      const response = await request.get(`${API_BASE}/mentorship/mentor-profile`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      
      console.log('✅ Mentor profile retrieved');
    });

    test('should create mentor profile', async ({ request }) => {
      const response = await request.post(`${API_BASE}/mentorship/mentor-profile`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        data: {
          years_experience: 8,
          specialties: ['Critical Care', 'Emergency Medicine'],
          mentoring_areas: ['Leadership Development', 'Clinical Excellence'],
          bio: 'Experienced nurse with 8 years in critical care. Passionate about mentoring new nurses.',
          mentoring_philosophy: 'Believe in hands-on learning and continuous support.',
          mentoring_style: 'coaching',
          max_mentees: 3,
          preferred_communication: ['video', 'email'],
          session_duration_minutes: 60,
          commitment_level: 'regular'
        }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('message');
      
      console.log('✅ Mentor profile created successfully');
    });

    test('should get mentee profile', async ({ request }) => {
      const response = await request.get(`${API_BASE}/mentorship/mentee-profile`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      
      console.log('✅ Mentee profile retrieved');
    });

    test('should create mentee profile', async ({ request }) => {
      const response = await request.post(`${API_BASE}/mentorship/mentee-profile`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        data: {
          career_goals: ['Leadership Development', 'Skill Building'],
          areas_for_development: ['Clinical Skills', 'Communication'],
          preferred_mentor_qualities: ['Experienced', 'Patient', 'Supportive'],
          experience_level: 'early_career',
          background_summary: 'New graduate nurse looking to develop clinical skills and leadership abilities.',
          what_seeking: 'Guidance on career advancement and skill development in nursing.',
          preferred_communication: ['video', 'email'],
          commitment_level: 'regular',
          has_had_mentor_before: false
        }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('message');
      
      console.log('✅ Mentee profile created successfully');
    });

    test('should find mentors', async ({ request }) => {
      const response = await request.get(`${API_BASE}/mentorship/find-mentors`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      
      console.log('✅ Mentors search completed');
    });

    test('should generate mentor matches', async ({ request }) => {
      const response = await request.post(`${API_BASE}/mentorship/generate-matches`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('message');
      expect(data).toHaveProperty('count');
      
      console.log(`✅ Generated ${data.count} mentor matches`);
    });

    test('should get mentor matches', async ({ request }) => {
      const response = await request.get(`${API_BASE}/mentorship/mentor-matches`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(Array.isArray(data.data)).toBeTruthy();
      
      console.log(`✅ Retrieved ${data.data.length} mentor matches`);
    });

    test('should get mentorship relationships', async ({ request }) => {
      const response = await request.get(`${API_BASE}/mentorship/relationships`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(Array.isArray(data.data)).toBeTruthy();
      
      console.log(`✅ Retrieved ${data.data.length} mentorship relationships`);
    });
  });

  // ═══════════════════════════════════════════════════════════
  // CERTIFICATION TRACKING CONTROLLER TESTS
  // ═══════════════════════════════════════════════════════════

  test.describe('Certification Tracking', () => {
    
    test('should get certification types', async ({ request }) => {
      const response = await request.get(`${API_BASE}/certification-tracking/certification-types`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(Array.isArray(data.data)).toBeTruthy();
      
      if (data.data.length > 0) {
        const certType = data.data[0];
        expect(certType).toHaveProperty('id');
        expect(certType).toHaveProperty('name');
        expect(certType).toHaveProperty('category');
      }
      
      console.log(`✅ Retrieved ${data.data.length} certification types`);
    });

    test('should get user certifications', async ({ request }) => {
      const response = await request.get(`${API_BASE}/certification-tracking/user-certifications`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(Array.isArray(data.data)).toBeTruthy();
      
      console.log(`✅ Retrieved ${data.data.length} user certifications`);
    });

    test('should add a certification', async ({ request }) => {
      // First get certification types
      const typesResponse = await request.get(`${API_BASE}/certification-tracking/certification-types`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      const typesData = await typesResponse.json();
      if (typesData.data.length === 0) {
        console.log('⚠️ No certification types available to test with');
        return;
      }
      
      const certTypeId = typesData.data[0].id;
      
      const response = await request.post(`${API_BASE}/certification-tracking/certifications`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        data: {
          certification_type_id: certTypeId,
          certification_number: 'TEST-CERT-' + Date.now(),
          issued_date: '2024-01-01',
          expiration_date: '2026-01-01',
          issuing_authority: 'Test Authority',
          notes: 'Test certification added via Playwright'
        }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('message');
      expect(data).toHaveProperty('id');
      
      console.log('✅ Certification added successfully');
    });

    test('should get renewals due', async ({ request }) => {
      const response = await request.get(`${API_BASE}/certification-tracking/renewals-due`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(Array.isArray(data.data)).toBeTruthy();
      
      console.log(`✅ Retrieved ${data.data.length} renewals due`);
    });

    test('should get certification analytics', async ({ request }) => {
      const response = await request.get(`${API_BASE}/certification-tracking/analytics`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      expect(response.ok()).toBeTruthy();
      const data = await response.json();
      
      expect(data).toHaveProperty('data');
      expect(data.data).toHaveProperty('total_certifications');
      expect(data.data).toHaveProperty('active_certifications');
      expect(data.data).toHaveProperty('expiring_soon');
      expect(data.data).toHaveProperty('renewals_due');
      
      console.log('✅ Certification analytics retrieved successfully');
    });
  });

  // ═══════════════════════════════════════════════════════════
  // FRONTEND NAVIGATION TESTS
  // ═══════════════════════════════════════════════════════════

  test.describe('Frontend Navigation', () => {
    
    test('should access Learning & Development page', async ({ page }) => {
      // Login first
      await page.goto(`${BASE_URL}/login`);
      await page.fill('input[type="email"]', EMPLOYER_CREDENTIALS.email);
      await page.fill('input[type="password"]', EMPLOYER_CREDENTIALS.password);
      await page.click('button[type="submit"]');
      
      // Wait for redirect to dashboard
      await page.waitForURL('**/employer/dashboard');
      
      // Navigate to Learning & Development
      await page.goto(`${BASE_URL}/employer/learning-development`);
      
      // Check if page loads correctly
      await expect(page.locator('h1')).toContainText('Learning & Development');
      
      console.log('✅ Learning & Development page accessible');
    });

    test('should access Mentorship page', async ({ page }) => {
      await page.goto(`${BASE_URL}/login`);
      await page.fill('input[type="email"]', EMPLOYER_CREDENTIALS.email);
      await page.fill('input[type="password"]', EMPLOYER_CREDENTIALS.password);
      await page.click('button[type="submit"]');
      
      await page.waitForURL('**/employer/dashboard');
      
      await page.goto(`${BASE_URL}/employer/mentorship`);
      
      await expect(page.locator('h1')).toContainText('Mentorship Program');
      
      console.log('✅ Mentorship page accessible');
    });

    test('should access Certification Tracking page', async ({ page }) => {
      await page.goto(`${BASE_URL}/login`);
      await page.fill('input[type="email"]', EMPLOYER_CREDENTIALS.email);
      await page.fill('input[type="password"]', EMPLOYER_CREDENTIALS.password);
      await page.click('button[type="submit"]');
      
      await page.waitForURL('**/employer/dashboard');
      
      await page.goto(`${BASE_URL}/employer/certifications`);
      
      await expect(page.locator('h1')).toContainText('Certification Tracking');
      
      console.log('✅ Certification Tracking page accessible');
    });
  });

  // ═══════════════════════════════════════════════════════════
  // ERROR HANDLING TESTS
  // ═══════════════════════════════════════════════════════════

  test.describe('Error Handling', () => {
    
    test('should handle unauthorized requests', async ({ request }) => {
      const response = await request.get(`${API_BASE}/learning-development/dashboard`);
      
      expect(response.status()).toBe(401);
      
      console.log('✅ Unauthorized access properly blocked');
    });

    test('should handle invalid skill ID', async ({ request }) => {
      const response = await request.post(`${API_BASE}/learning-development/user-skills`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        data: {
          skill_id: 99999,
          proficiency_level: 'intermediate'
        }
      });
      
      expect(response.status()).toBe(422);
      
      console.log('✅ Invalid skill ID properly rejected');
    });

    test('should handle invalid course enrollment', async ({ request }) => {
      const response = await request.post(`${API_BASE}/learning-development/courses/99999/enroll`, {
        headers: { 'Authorization': `Bearer ${authToken}` },
        data: {
          payment_status: 'free'
        }
      });
      
      expect(response.status()).toBe(404);
      
      console.log('✅ Invalid course enrollment properly rejected');
    });
  });

  // ═══════════════════════════════════════════════════════════
  // PERFORMANCE TESTS
  // ═══════════════════════════════════════════════════════════

  test.describe('Performance Tests', () => {
    
    test('should load dashboard within acceptable time', async ({ request }) => {
      const startTime = Date.now();
      
      const response = await request.get(`${API_BASE}/learning-development/dashboard`, {
        headers: { 'Authorization': `Bearer ${authToken}` }
      });
      
      const endTime = Date.now();
      const responseTime = endTime - startTime;
      
      expect(response.ok()).toBeTruthy();
      expect(responseTime).toBeLessThan(3000); // Should respond within 3 seconds
      
      console.log(`✅ Dashboard loaded in ${responseTime}ms`);
    });

    test('should handle concurrent requests', async ({ request }) => {
      const requests = [];
      
      // Make 5 concurrent requests
      for (let i = 0; i < 5; i++) {
        requests.push(
          request.get(`${API_BASE}/learning-development/skills-catalog`, {
            headers: { 'Authorization': `Bearer ${authToken}` }
          })
        );
      }
      
      const responses = await Promise.all(requests);
      
      // All requests should succeed
      responses.forEach(response => {
        expect(response.ok()).toBeTruthy();
      });
      
      console.log('✅ Concurrent requests handled successfully');
    });
  });
});