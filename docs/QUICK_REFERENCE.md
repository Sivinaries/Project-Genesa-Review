# GENESA Mobile API - Quick Reference

## Base URL
```
https://genesa.hries.id/api
```

## Authentication Header
```
Authorization: Bearer <YOUR_TOKEN_HERE>
```

## Login (Public)
```
POST /v1/login
{
  "email": "employee@example.com",
  "password": "password123"
}
```

---

## Employee Endpoints

### Home & Profile
```
GET /v1/home          - Dashboard data
GET /v1/profile       - Full profile
```

### Schedule
```
GET /v1/schedule?month=3&year=2026
```

### Attendance
```
GET /v1/attendance?month=3&year=2026&per_page=31
```

### Leave/Permission
```
GET /v1/leave?status=pending&type=cuti

POST /v1/leave
{
  "start_date": "2026-03-15",
  "end_date": "2026-03-17",
  "type": "cuti",           // izin, sakit, cuti, meninggalkan_pekerjaan, tukar_shift
  "note": "..."
}
```

### Overtime
```
GET /v1/overtime?status=pending

POST /v1/overtime
{
  "date": "2026-03-15",
  "duration": 2.5,
  "note": "..."
}
```

### Payroll
```
GET /v1/payroll?page=1&per_page=12
GET /v1/payroll/123           - Detail payroll
```

### Notes
```
GET /v1/note
```

### GPS Attendance
```
GET /v1/gps-attendance?month=3&year=2026

POST /v1/gps-attendance/checkin
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 5.5,
  "altitude": 50,            // optional
  "note": "..."              // optional
}

POST /v1/gps-attendance/checkout
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 5.5,
  "altitude": 50,            // optional
  "note": "..."              // optional
}
```

### Logout
```
POST /v1/logout
```

---

## Coordinator-Only Endpoints

### Manage Schedules
```
GET /v1/coordinator/schedule?month=3&year=2026&employee_id=5

POST /v1/coordinator/schedule
{
  "employee_id": 5,
  "date": "2026-03-15",
  "shift_id": 1,
  "note": "..."
}

PUT /v1/coordinator/schedule/123
{
  "shift_id": 2,
  "date": "2026-03-16",
  "note": "..."
}

DELETE /v1/coordinator/schedule/123
```

### Manage Leaves
```
GET /v1/coordinator/leave?status=pending&month=3

POST /v1/coordinator/leave
{
  "employee_id": 5,
  "start_date": "2026-03-15",
  "end_date": "2026-03-17",
  "type": "cuti",
  "note": "..."
}

PUT /v1/coordinator/leave/123
{
  "status": "approved",      // pending, approved, rejected
  "note": "..."
}
```

### Manage Overtime
```
GET /v1/coordinator/overtime?status=pending&month=3

POST /v1/coordinator/overtime
{
  "employee_id": 5,
  "date": "2026-03-15",
  "duration": 2.5,
  "note": "..."
}

PUT /v1/coordinator/overtime
{
  "overtime_ids": [1, 2, 3],
  "status": "approved",
  "note": "..."
}

DELETE /v1/coordinator/overtime
{
  "overtime_ids": [1, 2, 3]
}
```

---

## Response Format

**Success:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Success message"
}
```

**Error:**
```json
{
  "success": false,
  "data": null,
  "message": "Error message",
  "errors": { "field": ["error 1", "error 2"] }
}
```

---

## HTTP Status Codes
| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

## Common Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | int | 1-12 |
| `year` | int | YYYY |
| `page` | int | Pagination page |
| `per_page` | int | Records per page |
| `status` | string | pending, approved, rejected |
| `type` | string | Leave/permission type |
| `employee_id` | int | Filter by employee |

---

## Date/Time Formats

| Format | Example |
|--------|---------|
| Date | `2026-03-15` |
| DateTime | `2026-03-15T14:30:00+07:00` |
| Time | `14:30:00` |

---

## Testing with cURL

```bash
# Login
curl -X POST https://genesa.hries.id/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"pass"}'

# Get home (with token)
curl -X GET https://genesa.hries.id/api/v1/home \
  -H "Authorization: Bearer YOUR_TOKEN"

# Request leave
curl -X POST https://genesa.hries.id/api/v1/leave \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "start_date":"2026-03-15",
    "end_date":"2026-03-17",
    "type":"cuti",
    "note":"Cuti tahunan"
  }'
```

---

## Error Responses

**Validation Error (422):**
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

**Unauthorized (401):**
```json
{
  "success": false,
  "data": null,
  "message": "Unauthenticated"
}
```

**Not Found (404):**
```json
{
  "success": false,
  "data": null,
  "message": "Resource not found"
}
```

---

## Integration Tips

1. **Always include token in Authorization header for protected endpoints**
2. **Use POST with JSON for creating/updating resources**
3. **Use GET with query parameters for filtering**
4. **Always check `success` field before processing `data`**
5. **Handle validation errors with the `errors` field**
6. **Implement logout and re-login on 401 Unauthorized**
7. **Format dates as ISO 8601 (YYYY-MM-DD)**
8. **GPS coordinates in decimal format (latitude, longitude)**

---

## Full Documentation

See `docs/openapi.yaml` for complete API specification (OpenAPI 3.0 format).

Import into Swagger UI, Postman, or Insomnia for interactive documentation.

See `docs/API_SETUP.md` for detailed setup guide with examples.
