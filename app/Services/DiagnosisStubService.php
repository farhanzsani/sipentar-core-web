<?php

namespace App\Services;

use App\Services\Contracts\DiagnosisServiceInterface;

/**
 * Stub implementation of DiagnosisServiceInterface.
 * Returns random classification results for testing.
 * 
 * This is a temporary service used while the Python CV service is being developed.
 * Switch to DiagnosisHttpService in AppServiceProvider when the Python service is ready.
 */
class DiagnosisStubService implements DiagnosisServiceInterface
{
    /**
     * Classify plant disease from image (stub implementation).
     * Returns random results from the four diagnosis classes.
     *
     * @param string $imagePath Full path to the uploaded image file
     * @return array Array with 'hasil' and 'confidence' keys
     */
    public function classify(string $imagePath): array
    {
        $classes = ['sehat', 'busuk_batang', 'cacar_daun', 'busuk_akar'];
        $hasil = $classes[array_rand($classes)];
        $confidence = rand(7000, 9900) / 100; // Random confidence between 70.00 and 99.00

        return [
            'hasil' => $hasil,
            'confidence' => $confidence,
        ];
    }
}
