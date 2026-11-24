<?php

namespace App\Services\Contracts;

interface DiagnosisServiceInterface
{
    /**
     * Classify plant disease from image.
     *
     * @param string $imagePath Full path to the uploaded image file
     * @return array Array with 'hasil' (string) and 'confidence' (float) keys
     */
    public function classify(string $imagePath): array;
}
