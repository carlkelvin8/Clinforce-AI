import { APIRequestContext } from '@playwright/test';

const BASE = process.env.TEST_BASE_URL || 'http://localhost:8000';
const API = `${BASE}/api`;

export interface AuthTokens {
  employer: string;
  applicant: string;
  admin: string;
}

let _tokens: Partial<AuthTokens> = {};

export async function loginAs(
  request: APIRequestContext,
  role: 'employer' | 'applicant' | 'admin'
): Promise<string> {
  if (_tokens[role]) return _tokens[role]!;

  const creds: Record<string, { identifier: string; password: string }> = {
    employer:  { identifier: process.env.TEST_EMPLOYER_EMAIL  || 'employer@test.com',  password: process.env.TEST_PASSWORD || 'Password1!' },
    applicant: { identifier: process.env.TEST_APPLICANT_EMAIL || 'applicant@test.com', password: process.env.TEST_PASSWORD || 'Password1!' },
    admin:     { identifier: process.env.TEST_ADMIN_EMAIL     || 'admin@test.com',     password: process.env.TEST_PASSWORD || 'Password1!' },
  };

  const res = await request.post(`${API}/auth/login`, { data: creds[role] });
  const body = await res.json();
  const token = body?.data?.token;
  if (!token) throw new Error(`Login failed for ${role}: ${JSON.stringify(body)}`);
  _tokens[role] = token;
  return token;
}

export function authHeaders(token: string) {
  return { Authorization: `Bearer ${token}` };
}

export { API, BASE };
