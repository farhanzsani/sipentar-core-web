# Diagnosis Integration Guide

This document explains how the plant disease diagnosis feature integrates with the Python Computer Vision service.

## Current State: Stub Service

The diagnosis feature is currently running with **DiagnosisStubService**, which returns fake/random classification results for testing and development purposes. This allows:

- Frontend development to proceed independently
- API testing without requiring the Python CV service
- End-to-end workflow validation
- Early user acceptance testing

The stub service:
- Randomly selects from 4 diagnosis classes: `sehat`, `busuk_batang`, `cacar_daun`, `busuk_akar`
- Returns random confidence values between 70.00 and 99.00
- Provides appropriate recommendations from the configuration file

## Future State: Python CV Service Integration

When the Python Computer Vision service is ready, the diagnosis feature will use **DiagnosisHttpService** to send real plant images to the ML model for classification.

### Python Service Contract

The Python service must expose the following HTTP endpoint:

**Endpoint:** `POST {CV_SERVICE_URL}/api/diagnose`

**Request:**
- Method: POST
- Content-Type: multipart/form-data
- Body: `image` (file) - The plant image to classify

**Response:**
- Content-Type: application/json
- Body:
```json
{
  "result": "sehat|busuk_batang|cacar_daun|busuk_akar",
  "confidence": 87.45
}
```

**Expected Behavior:**
- `result`: Must be one of: `sehat`, `busuk_batang`, `cacar_daun`, `busuk_akar`
- `confidence`: Float value between 0 and 100 representing prediction confidence
- HTTP 200 status for successful classification
- HTTP 4xx/5xx status for errors (invalid image, service unavailable, etc.)

### Configuration

Set the Python service URL in your `.env` file:

```env
CV_SERVICE_URL=http://localhost:5000
```

For production:
```env
CV_SERVICE_URL=https://cv-service.sipentar.com
```

## Switching from Stub to Production

To activate the real Python CV service:

### 1. Update AppServiceProvider

Edit `app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    // Change this binding:
    $this->app->bind(
        \App\Services\Contracts\DiagnosisServiceInterface::class,
        \App\Services\DiagnosisHttpService::class  // Changed from DiagnosisStubService
    );
}
```

### 2. Update Environment Configuration

Set the correct CV service URL in `.env`:

```env
CV_SERVICE_URL=https://your-python-service-url.com
```

### 3. Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### 4. Restart Application

If using Laravel Octane or a process manager:
```bash
php artisan octane:reload
# or
supervisorctl restart laravel-worker:*
```

## Error Handling

The `DiagnosisHttpService` handles several error scenarios:

### Connection Errors
If the Python service is unreachable:
- Exception: `Unable to connect to CV service: {error}`
- HTTP 500 response to client
- Original image is deleted (transaction rollback)

### Service Errors
If the Python service returns HTTP 4xx/5xx:
- Exception: `CV service returned error: {status} - {body}`
- HTTP 500 response to client
- Original image is deleted (transaction rollback)

### Invalid Response
If the Python service returns malformed JSON:
- Exception: `CV service returned invalid response format`
- HTTP 500 response to client
- Original image is deleted (transaction rollback)

### Timeout
Default timeout is 30 seconds. If classification takes longer:
- Exception: `Connection timeout`
- HTTP 500 response to client

## Testing

### Testing with Stub Service
The stub service is ideal for:
- Unit and feature tests (no external dependencies)
- Frontend development and integration
- Load testing
- Demonstration to stakeholders

### Testing with Real Python Service
When the Python service is available:

1. Set `CV_SERVICE_URL` to the test/staging Python service
2. Switch to `DiagnosisHttpService` in `AppServiceProvider`
3. Clear config cache
4. Test with real plant images
5. Verify confidence scores are reasonable
6. Verify recommendations match the diagnosis

### Manual Testing with cURL

```bash
# Test Python service directly
curl -X POST \
  -F "image=@/path/to/plant-image.jpg" \
  http://localhost:5000/api/diagnose

# Test Laravel API
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -F "image=@/path/to/plant-image.jpg" \
  -F "budidaya_id=1" \
  http://localhost:8000/api/v1/diagnosis
```

## Monitoring and Observability

Consider adding monitoring for production:

1. **Response Time**: Track how long Python service calls take
2. **Error Rate**: Monitor connection failures and service errors
3. **Classification Distribution**: Track frequency of each diagnosis type
4. **Confidence Scores**: Monitor average confidence to detect model drift

## Recommendations Configuration

Plant disease recommendations are stored in `config/sipentar.php`:

```php
'diagnosis_recommendations' => [
    'sehat' => '...',
    'busuk_batang' => '...',
    'cacar_daun' => '...',
    'busuk_akar' => '...',
]
```

These can be updated without code changes by modifying the configuration file.

## Security Considerations

1. **Image Storage**: Images are stored in `storage/app/public/diagnosis/` with random 40-character filenames
2. **File Validation**: Only jpg, jpeg, png files accepted, max 5MB
3. **Rate Limiting**: POST endpoint limited to 10 requests per minute per user
4. **Authentication**: All diagnosis endpoints require Sanctum authentication
5. **Authorization**: Both `pemilik` and `pegawai` roles can create diagnoses

## API Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/diagnosis` | List all diagnoses (paginated, filterable) | Required |
| POST | `/api/v1/diagnosis` | Create new diagnosis | Required |
| GET | `/api/v1/diagnosis/{id}` | Get single diagnosis | Required |

### Filters

The list endpoint supports:
- `budidaya_id`: Filter by cultivation ID
- `hasil`: Filter by diagnosis result (sehat, busuk_batang, cacar_daun, busuk_akar)
- `from`: Filter by diagnosis date >= (Y-m-d format)
- `to`: Filter by diagnosis date <= (Y-m-d format)
- `per_page`: Results per page (default: 15)

## Troubleshooting

### "CV service unavailable" error
- Check if Python service is running
- Verify `CV_SERVICE_URL` in `.env`
- Check network connectivity
- Review Python service logs

### "CV service returned invalid response" error
- Verify Python service returns correct JSON format
- Check that `result` and `confidence` keys exist
- Ensure `result` is one of the 4 valid values

### Images not displaying
- Run `php artisan storage:link` to create public symlink
- Verify `storage/app/public/diagnosis/` directory exists and is writable
- Check file permissions

### High response times
- Increase timeout in `DiagnosisHttpService` if needed
- Optimize Python service performance
- Consider caching mechanism if same images are classified repeatedly

## Support

For issues related to:
- Laravel API: Contact backend team
- Python CV service: Contact Python/ML team
- Integration: Review this documentation or contact DevOps team
