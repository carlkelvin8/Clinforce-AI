import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  thresholds: {
    http_req_failed: ['rate<0.01'], // <1% errors
    http_req_duration: ['p(95)<500'], // 95% requests < 500ms
  },
  stages: [
    { duration: '1m', target: 100 },  // warm-up
    { duration: '3m', target: 500 },  // 5x baseline
    { duration: '3m', target: 1000 }, // 10x baseline
    { duration: '2m', target: 0 },    // ramp-down
  ],
};

const BASE = __ENV.BASE_URL || 'http://localhost';

export default function () {
  const res = http.get(`${BASE}/api/public/jobs`);
  check(res, {
    'status is 200': (r) => r.status === 200,
  });
  sleep(0.3);
}
