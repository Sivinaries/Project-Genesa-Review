# GENESA Mobile API - Developer Setup Guide

## Quick Start

### 1. API Documentation
The complete API specification is documented in OpenAPI 3.0 format at `docs/openapi.yaml`.

**To view the documentation interactively:**
- Use [Swagger UI](https://editor.swagger.io/) - Import the `openapi.yaml` file
- Use [Postman](https://www.postman.com/) - Import the endpoint collection
- Use [Insomnia](https://insomnia.rest/) - Import the OpenAPI spec

### 2. Authentication

All protected endpoints require Sanctum API Token authentication.

**Login Flow:**
```bash
# 1. POST /v1/login with credentials
curl -X POST https://genesa.hries.id/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "employee@example.com",
    "password": "password123"
  }'

# Response includes token:
{
  "success": true,
  "data": {
    "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz...",
    "employee": {...}
  },
  "message": "Login berhasil"
}
```

**Using Token for Authenticated Requests:**
```bash
# Add Authorization header to all protected endpoints
curl -X GET https://genesa.hries.id/api/v1/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 3. Response Format

All API responses follow a consistent JSON format:

```json
{
  "success": true,
  "data": {
    // Response data - varies by endpoint
  },
  "message": "Descriptive message"
}
```

**Error Response:**
```json
{
  "success": false,
  "data": null,
  "message": "Error description"
}
```

### 4. Base URLs

**Production:**
- `https://genesa.hries.id/api`

**Local Development:**
- `http://localhost/api`

## API Endpoints Overview

### Public Endpoints (No Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/v1/login` | Employee login |
| POST | `/fingerspot/webhook` | Fingerspot biometric webhook |

### Employee Endpoints (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/v1/logout` | Logout current user |
| GET | `/v1/home` | Dashboard home data |
| GET | `/v1/profile` | Employee profile details |
| GET | `/v1/schedule` | Work schedule |
| GET | `/v1/attendance` | Attendance records |
| GET | `/v1/leave` | Leave/permission list |
| POST | `/v1/leave` | Request leave/permission |
| GET | `/v1/overtime` | Overtime list |
| POST | `/v1/overtime` | Request overtime |
| GET | `/v1/payroll` | Payroll slip list |
| GET | `/v1/payroll/{id}` | Payroll detail |
| GET | `/v1/note` | Notes list |
| GET | `/v1/gps-attendance` | GPS attendance log |
| POST | `/v1/gps-attendance/checkin` | GPS check-in |
| POST | `/v1/gps-attendance/checkout` | GPS check-out |

### Coordinator Endpoints (Auth Required + Coordinator Role)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/coordinator/schedule` | Managed employees schedules |
| POST | `/v1/coordinator/schedule` | Create schedule |
| PUT | `/v1/coordinator/schedule/{id}` | Update schedule |
| DELETE | `/v1/coordinator/schedule/{id}` | Delete schedule |
| GET | `/v1/coordinator/leave` | Managed employees leaves |
| POST | `/v1/coordinator/leave` | Create leave |
| PUT | `/v1/coordinator/leave/{id}` | Update leave |
| GET | `/v1/coordinator/overtime` | Managed employees overtime |
| POST | `/v1/coordinator/overtime` | Create overtime |
| PUT | `/v1/coordinator/overtime` | Batch update overtime |
| DELETE | `/v1/coordinator/overtime` | Batch delete overtime |

## Common Use Cases

### 1. Login and Get Home Dashboard

```bash
# Step 1: Login
curl -X POST https://genesa.hries.id/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "employee@example.com",
    "password": "password123"
  }'

# Save the returned token

# Step 2: Get home dashboard with token
curl -X GET https://genesa.hries.id/api/v1/home \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. Get Month's Attendance

```bash
curl -X GET "https://genesa.hries.id/api/v1/attendance?month=3&year=2026" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Request Leave

```bash
curl -X POST https://genesa.hries.id/api/v1/leave \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2026-03-15",
    "end_date": "2026-03-17",
    "type": "cuti",
    "note": "Cuti tahunan"
  }'
```

### 4. GPS Check-in

```bash
curl -X POST https://genesa.hries.id/api/v1/gps-attendance/checkin \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "accuracy": 5.5,
    "note": "Check-in from office"
  }'
```

### 5. Get Payroll Detail

```bash
curl -X GET https://genesa.hries.id/api/v1/payroll/123 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Error Handling

### HTTP Status Codes

| Code | Meaning | Typical Cause |
|------|---------|---------------|
| 200 | OK | Successful GET request |
| 201 | Created | Successful POST/resource creation |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Missing or invalid token |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation error (see message) |
| 500 | Server Error | Server-side issue |

### Validation Error Example

```json
{
  "success": false,
  "data": null,
  "message": "Validasi gagal",
  "errors": {
    "start_date": ["Start date is required"],
    "end_date": ["End date must be after start date"]
  }
}
```

## Token Management

### Token Expiration & Refresh

- Tokens are issued per login
- Each new login invalidates previous tokens
- No automatic refresh - login again to get a new token
- Tokens are stored as Sanctum API tokens in database

### Logout

```bash
curl -X POST https://genesa.hries.id/api/v1/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Rate Limiting

Currently no rate limiting is implemented. Rate limits may be added in future versions.

## Best Practices

1. **Always use HTTPS** in production
2. **Store tokens securely** - never hardcode tokens
3. **Implement token refresh** - logout and login if token seems expired
4. **Handle errors gracefully** - always check the response status
5. **Validate input** before sending to API
6. **Use pagination** for list endpoints to avoid large responses
7. **Implement retry logic** for network failures

## Pagination

List endpoints that support pagination:

```bash
# Example: Get payroll with pagination
curl -X GET "https://genesa.hries.id/api/v1/payroll?page=1&per_page=12" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Records per page (varies by endpoint)

## Date/Time Format

- **Dates**: ISO 8601 format (`YYYY-MM-DD`)
- **DateTime**: ISO 8601 format with timezone (`YYYY-MM-DDTHH:MM:SS+00:00`)
- **Time**: 24-hour format (`HH:MM:SS`)

## Example Integration (JavaScript/Node.js)

```javascript
// Install: npm install axios
import axios from 'axios';

const API_BASE = 'https://genesa.hries.id/api';

// Create axios instance
const api = axios.create({
  baseURL: API_BASE,
  headers: { 'Content-Type': 'application/json' }
});

// Add response interceptor for error handling
api.interceptors.response.use(
  response => response.data,
  error => {
    if (error.response?.status === 401) {
      // Handle unauthorized - redirect to login
      window.location.href = '/login';
    }
    return Promise.reject(error.response?.data || error);
  }
);

// Login
async function login(email, password) {
  const response = await api.post('/v1/login', { email, password });
  localStorage.setItem('token', response.data.token);
  api.defaults.headers.Authorization = `Bearer ${response.data.token}`;
  return response.data;
}

// Set token from storage on app load
const token = localStorage.getItem('token');
if (token) {
  api.defaults.headers.Authorization = `Bearer ${token}`;
}

// Get home dashboard
async function getHome() {
  return api.get('/v1/home');
}

// Request leave
async function requestLeave(startDate, endDate, type, note) {
  return api.post('/v1/leave', {
    start_date: startDate,
    end_date: endDate,
    type,
    note
  });
}

// GPS Check-in
async function gpsCheckIn(latitude, longitude, accuracy, note) {
  return api.post('/v1/gps-attendance/checkin', {
    latitude,
    longitude,
    accuracy,
    note
  });
}

// Logout
async function logout() {
  await api.post('/v1/logout');
  localStorage.removeItem('token');
  delete api.defaults.headers.Authorization;
  window.location.href = '/login';
}
```

## Support & Troubleshooting

**Common Issues:**

1. **401 Unauthorized**
   - Check if token is included in Authorization header
   - Verify token format: `Bearer <token>`
   - Login again to get a new token

2. **422 Validation Error**
   - Check the `errors` field in response
   - Verify date formats are ISO 8601
   - Check required fields are included

3. **404 Not Found**
   - Verify the endpoint URL is correct
   - Check resource ID exists
   - For coordinator endpoints, verify user has coordinator role

4. **500 Server Error**
   - Check server logs
   - Contact support with error details and timestamp

## Changelog

### Version 1.0.0 (Current)
- Initial API release
- Employee ESS features (attendance, schedule, leave, overtime, payroll)
- Coordinator management for schedules, leaves, and overtime
- GPS attendance tracking
- Sanctum authentication

## Future Improvements

- Rate limiting
- Token refresh endpoint
- Advanced filtering and search
- Bulk operations
- Webhooks for status updates
- Mobile app specific endpoints
