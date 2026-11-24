<?php

namespace App\Services;

use App\Services\Contracts\DiagnosisServiceInterface;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * HTTP implementation of DiagnosisServiceInterface.
 * Connects to the Python Computer Vision service for real plant disease classification.
 * 
 * Python API Contract:
 * - Endpoint: POST {CV_SERVICE_URL}/api/diagnose
 * - Request: multipart/form-data with 'image' file
 * - Response: JSON {"result": "sehat|busuk_batang|cacar_daun|busuk_akar", "confidence": 0-100}
 * 
 * To activate: Change AppServiceProvider binding from DiagnosisStubService to DiagnosisHttpService
 */
class DiagnosisHttpService implements DiagnosisServiceInterface
{
    /**
     * Classify plant disease from image using Python CV service.
     *
     * @param string $imagePath Full path to the uploaded image file
     * @return array Array with 'hasil' and 'confidence' keys
     * @throws Exception When CV service is unavailable or returns invalid response
     */
    public function classify(string $imagePath): array
    {
        $url = config('sipentar.cv_service_url') . '/api/diagnose';

        try {
            $response = Http::timeout(30)
                ->attach('image', file_get_contents($imagePath), basename($imagePath))
                ->post($url);

            if (!$response->successful()) {
                throw new Exception(
                    'CV service returned error: ' . $response->status() . ' - ' . $response->body()
                );
            }

            $data = $response->json();

            if (!isset($data['result']) || !isset($data['confidence'])) {
                throw new Exception('CV service returned invalid response format');
            }

            return [
                'hasil' => $data['result'],
                'confidence' => $data['confidence'],
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new Exception('Unable to connect to CV service: ' . $e->getMessage());
        }
    }
}
