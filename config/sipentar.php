<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HPP Per Hole (Harga Pokok Produksi per Lubang)
    |--------------------------------------------------------------------------
    |
    | Default cost per planting hole in Rupiah
    |
    */
    'hpp_per_hole' => env('HPP_PER_HOLE', 1200),

    /*
    |--------------------------------------------------------------------------
    | Cash Allocation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for cash allocation percentage (% kas operasional)
    |
    */
    'cash_allocation' => [
        'min' => env('CASH_ALLOCATION_MIN', 30),
        'max' => env('CASH_ALLOCATION_MAX', 40),
        'default' => env('CASH_ALLOCATION_DEFAULT', 35),
    ],

    /*
    |--------------------------------------------------------------------------
    | Diagnosis Recommendations
    |--------------------------------------------------------------------------
    |
    | Text recommendations for each diagnosis result
    |
    */
    'diagnosis_recommendations' => [
        'sehat' => 'Tanaman dalam kondisi baik. Lanjutkan perawatan rutin dengan penyiraman teratur dan pemantauan berkala.',
        'busuk_batang' => 'Terdeteksi busuk batang. Segera isolasi tanaman yang terinfeksi, kurangi kelembaban, dan tingkatkan sirkulasi udara. Pertimbangkan penggunaan fungisida organik.',
        'cacar_daun' => 'Terdeteksi cacar daun. Buang daun yang terinfeksi, hindari penyiraman dari atas, dan berikan jarak antar tanaman. Gunakan fungisida bila diperlukan.',
        'busuk_akar' => 'Terdeteksi busuk akar. Periksa sistem drainase, kurangi frekuensi penyiraman, dan pastikan media tanam tidak terlalu lembab. Pertimbangkan untuk mencabut tanaman yang parah terinfeksi.',
    ],

    /*
    |--------------------------------------------------------------------------
    | CV Service URL
    |--------------------------------------------------------------------------
    |
    | URL endpoint for Computer Vision AI diagnosis service
    |
    */
    'cv_service_url' => env('CV_SERVICE_URL', 'http://localhost:5000'),

];
