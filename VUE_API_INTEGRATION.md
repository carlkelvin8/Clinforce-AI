 # Vue Frontend - API Integration Guide

## How Vue Calls the Backend API

Your Vue frontend on Hostinger will call the Laravel API on Railway.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    User's Browser                            │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Vue Frontend (Hostinger)                            │  │
│  │  https://your-hostinger-domain.com                   │  │
│  │                                                      │  │
│  │  Makes API calls to:                                │  │
│  │  https://your-railway-domain.com/api                │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│                          │ HTTP/HTTPS                        │
│                          ▼                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Laravel API (Railway)                               │  │
│  │  https://your-railway-domain.com/api                 │  │
│  │                                                      │  │
│  │  - Handles authentication                            │  │
│  │  - Processes business logic                          │  │
│  │  - Connects to MySQL database                        │  │
│  │  - Returns JSON responses                            │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│                          │ SQL                               │
│                          ▼                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  MySQL Database (Railway)                            │  │
│  │  Stores all application data                         │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## Configuration

### 1. Environment Variables

**For Vue (Hostinger):**

Create `.env.production.local`:
```env
VITE_API_URL=https://your-railway-domain.com/api
```

**For Laravel (Railway):**

Set in Railway dashboard:
```
APP_URL=https://your-railway-domain.com
APP_FRONTEND_URL=https://your-hostinger-domain.com
```

### 2. API Client Setup

The Vue app uses Axios to make API calls. Configuration in `resources/js/lib/api.js`:

```javascript
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000/api",
  timeout: 20000,
  withCredentials: true,
});
```

**What this does:**
- `baseURL` - Sets the API server address
- `timeout` - Waits max 20 seconds for response
- `withCredentials` - Sends cookies for authentication

### 3. CORS Configuration

Laravel must allow requests from your Vue domain.

Update `config/cors.php`:

```php
'allowed_origins' => [
    'https://your-hostinger-domain.com',
    'https://www.your-hostinger-domain.com',
    'http://localhost:5173', // for local development
],
```

Then push to GitHub so Railway redeploys.

## How API Calls Work

### Example: Login Request

```javascript
// In Vue component
import api from '@/lib/api'

async function login(email, password) {
  try {
    const response = await api.post('/auth/login', {
      email: email,
      password: password
    })
    
    // Response from Railway API
    console.log(response.data)
    // {
    //   "user": { "id": 1, "email": "user@example.com" },
    //   "token": "1|abc123xyz..."
    // }
    
  } catch (error) {
    console.error('Login failed:', error)
  }
}
```

### Request Flow

1. **Vue makes request:**
   ```
   POST https://your-railway-domain.com/api/auth/login
   {
     "email": "user@example.com",
     "password": "password123"
   }
   ```

2. **Railway receives request:**
   - Validates CORS (checks if request is from allowed domain)
   - Processes authentication
   - Queries database
   - Returns response

3. **Vue receives response:**
   ```
   {
     "user": { "id": 1, "email": "user@example.com" },
     "token": "1|abc123xyz..."
   }
   ```

4. **Vue stores token:**
   ```javascript
   localStorage.setItem('auth_token', response.data.token)
   ```

5. **Future requests include token:**
   ```
   Authorization: Bearer 1|abc123xyz...
   ```

## API Endpoints

Your Vue app can call any endpoint defined in `routes/api.php`:

### Authentication
```javascript
api.post('/auth/login', { email, password })
api.post('/auth/register', { name, email, password })
api.post('/auth/logout')
api.get('/me')
```

### Jobs
```javascript
api.get('/jobs')
api.get('/jobs/:id')
api.post('/jobs', jobData)
api.put('/jobs/:id', jobData)
api.delete('/jobs/:id')
```

### Applications
```javascript
api.get('/job-applications')
api.post('/job-applications', applicationData)
api.put('/job-applications/:id/status', { status })
```

### Messages
```javascript
api.get('/conversations')
api.post('/conversations', { participants })
api.get('/conversations/:id/messages')
api.post('/conversations/:id/messages', { body })
```

### Profile
```javascript
api.get('/me/employer')
api.put('/me/employer', profileData)
api.get('/me/applicant')
api.put('/me/applicant', profileData)
```

### Health Check
```javascript
api.get('/health')
// Returns: { "status": "ok", "checks": { "app": true, "db": true } }
```

## Error Handling

### Common Errors

**401 Unauthorized:**
```javascript
// Token expired or invalid
// Vue should redirect to login
```

**403 Forbidden:**
```javascript
// User doesn't have permission
// Show error message to user
```

**404 Not Found:**
```javascript
// Resource doesn't exist
// Show "Not found" message
```

**500 Server Error:**
```javascript
// Backend error
// Check Railway logs
```

### Error Handling in Vue

```javascript
async function fetchData() {
  try {
    const response = await api.get('/data')
    return response.data
  } catch (error) {
    if (error.response?.status === 401) {
      // Redirect to login
      router.push('/login')
    } else if (error.response?.status === 403) {
      // Show permission error
      alert('You do not have permission')
    } else if (error.response?.status === 404) {
      // Show not found
      alert('Resource not found')
    } else {
      // Show generic error
      alert('An error occurred: ' + error.message)
    }
  }
}
```

## Testing API Calls

### Test in Browser Console

```javascript
// Open browser DevTools (F12)
// Go to Console tab

// Test health endpoint
fetch('https://your-railway-domain.com/api/health')
  .then(r => r.json())
  .then(d => console.log(d))

// Test login
fetch('https://your-railway-domain.com/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'test@example.com',
    password: 'password123'
  })
})
  .then(r => r.json())
  .then(d => console.log(d))
```

### Test with Postman

1. Download Postman: https://www.postman.com/downloads/
2. Create new request
3. Set method to POST
4. Set URL to: `https://your-railway-domain.com/api/auth/login`
5. Go to Body tab
6. Select "raw" and "JSON"
7. Enter:
   ```json
   {
     "email": "test@example.com",
     "password": "password123"
   }
   ```
8. Click Send

## Debugging API Issues

### Issue: CORS Error
```
Access to XMLHttpRequest blocked by CORS policy
```

**Solution:**
1. Update `config/cors.php` on Laravel
2. Add your Hostinger domain
3. Push to GitHub
4. Wait for Railway to redeploy

### Issue: 404 Not Found
```
POST https://your-railway-domain.com/api/auth/login 404
```

**Solution:**
1. Verify API URL is correct
2. Check route exists in `routes/api.php`
3. Check Railway is running

### Issue: Connection Timeout
```
Error: timeout of 20000ms exceeded
```

**Solution:**
1. Check Railway is running
2. Check database is connected
3. Check for slow queries
4. Increase timeout in `api.js`

### Issue: 500 Server Error
```
POST https://your-railway-domain.com/api/auth/login 500
```

**Solution:**
1. Check Railway logs: `railway logs`
2. Check database connection
3. Check environment variables
4. Check for PHP errors

## Deployment Checklist

- [ ] Vue built: `npm run build`
- [ ] `.env.production.local` has correct API URL
- [ ] Vue uploaded to Hostinger
- [ ] `.htaccess` uploaded to Hostinger
- [ ] Laravel environment variables set on Railway
- [ ] CORS configured in `config/cors.php`
- [ ] Laravel pushed to GitHub
- [ ] Railway redeployed
- [ ] Test health endpoint: `https://your-railway-domain.com/api/health`
- [ ] Test login from Vue app
- [ ] Check browser console for errors
- [ ] Check Network tab for failed requests

## Summary

```
Vue (Hostinger)
    ↓
    Makes HTTP requests to
    ↓
Laravel API (Railway)
    ↓
    Queries MySQL Database
    ↓
    Returns JSON response
    ↓
Vue displays data to user
```

**Everything is configured and ready to work!** 🚀

Just make sure:
1. ✅ Vue has correct API URL
2. ✅ Laravel has CORS enabled
3. ✅ Both are deployed
4. ✅ Environment variables are set
