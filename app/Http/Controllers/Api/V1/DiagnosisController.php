<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DiagnosisStoreRequest;
use App\Http\Resources\Api\V1\DiagnosisResource;
use App\Models\Diagnosis;
use App\Services\Contracts\DiagnosisServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class DiagnosisController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DiagnosisServiceInterface $diagnosisService
    ) {}

    #[OA\Get(
        path: '/api/v1/diagnosis',
        summary: 'Daftar diagnosis kesehatan tanaman',
        security: [['bearerAuth' => []]],
        tags: ['Diagnosis'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'budidaya_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'hasil', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['sehat', 'busuk_batang', 'cacar_daun', 'busuk_akar'])),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data diagnosis berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data diagnosis berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                            new OA\Property(property: 'budidaya_id', type: 'integer', example: 2),
                                            new OA\Property(property: 'image_url', type: 'string', example: 'http://localhost:8000/storage/diagnosis/abc123.jpg'),
                                            new OA\Property(property: 'hasil', type: 'string', example: 'busuk_batang'),
                                            new OA\Property(property: 'confidence', type: 'string', example: '87.45'),
                                            new OA\Property(property: 'rekomendasi', type: 'string', example: 'Terdeteksi busuk batang. Segera isolasi tanaman yang terinfeksi...'),
                                            new OA\Property(property: 'tanggal_diagnosis', type: 'string', format: 'date-time', example: '2026-11-22 14:30:00'),
                                            new OA\Property(property: 'disclaimer', type: 'string', example: 'Hasil ini hanya informasi pendukung, bukan pengganti pemeriksaan langsung.'),
                                            new OA\Property(
                                                property: 'user',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                                    new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                                                    new OA\Property(property: 'username', type: 'string', example: 'budi_santoso'),
                                                    new OA\Property(property: 'role', type: 'string', example: 'pegawai')
                                                ],
                                                type: 'object'
                                            ),
                                            new OA\Property(
                                                property: 'budidaya',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 2),
                                                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                                    new OA\Property(property: 'meja_id', type: 'integer', example: 3),
                                                    new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date', example: '2026-11-01'),
                                                    new OA\Property(property: 'jumlah_tanaman', type: 'integer', example: 50),
                                                    new OA\Property(property: 'status', type: 'string', example: 'berjalan'),
                                                    new OA\Property(property: 'hss', type: 'integer', example: 21)
                                                ],
                                                type: 'object'
                                            ),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T14:30:00.000000Z'),
                                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T14:30:00.000000Z')
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'links',
                                    properties: [
                                        new OA\Property(property: 'first', type: 'string', example: 'http://localhost:8000/api/v1/diagnosis?page=1'),
                                        new OA\Property(property: 'last', type: 'string', example: 'http://localhost:8000/api/v1/diagnosis?page=2'),
                                        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                                        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://localhost:8000/api/v1/diagnosis?page=2')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'meta',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'last_page', type: 'integer', example: 2),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                        new OA\Property(property: 'total', type: 'integer', example: 23)
                                    ],
                                    type: 'object'
                                )
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Diagnosis::with(['user', 'budidaya']);

        // Filter by budidaya_id
        if ($request->filled('budidaya_id')) {
            $query->where('budidaya_id', $request->budidaya_id);
        }

        // Filter by hasil
        if ($request->filled('hasil')) {
            $query->where('hasil', $request->hasil);
        }

        // Filter by date range (from)
        if ($request->filled('from')) {
            $query->whereDate('tanggal_diagnosis', '>=', $request->from);
        }

        // Filter by date range (to)
        if ($request->filled('to')) {
            $query->whereDate('tanggal_diagnosis', '<=', $request->to);
        }

        // Order by most recent diagnosis
        $query->orderBy('tanggal_diagnosis', 'desc');

        $perPage = $request->get('per_page', 15);
        $diagnoses = $query->paginate($perPage);

        return $this->successResponse(
            DiagnosisResource::collection($diagnoses)->response()->getData(true),
            'Data diagnosis berhasil diambil.'
        );
    }

    #[OA\Post(
        path: '/api/v1/diagnosis',
        summary: 'Buat diagnosis kesehatan tanaman baru',
        security: [['bearerAuth' => []]],
        tags: ['Diagnosis'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['image', 'budidaya_id'],
                    properties: [
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Image file (jpg, jpeg, png, max 5MB)'),
                        new OA\Property(property: 'budidaya_id', type: 'integer', description: 'ID budidaya yang didiagnosis')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Diagnosis berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Diagnosis berhasil dibuat.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'budidaya_id', type: 'integer', example: 2),
                                new OA\Property(property: 'image_url', type: 'string', example: 'http://localhost:8000/storage/diagnosis/abc123.jpg'),
                                new OA\Property(property: 'hasil', type: 'string', example: 'busuk_batang'),
                                new OA\Property(property: 'confidence', type: 'string', example: '87.45'),
                                new OA\Property(property: 'rekomendasi', type: 'string', example: 'Terdeteksi busuk batang. Segera isolasi tanaman yang terinfeksi...'),
                                new OA\Property(property: 'tanggal_diagnosis', type: 'string', format: 'date-time', example: '2026-11-22 14:30:00'),
                                new OA\Property(property: 'disclaimer', type: 'string', example: 'Hasil ini hanya informasi pendukung, bukan pengganti pemeriksaan langsung.'),
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                                        new OA\Property(property: 'username', type: 'string', example: 'budi_santoso'),
                                        new OA\Property(property: 'role', type: 'string', example: 'pegawai')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'budidaya',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                        new OA\Property(property: 'meja_id', type: 'integer', example: 3),
                                        new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date', example: '2026-11-01'),
                                        new OA\Property(property: 'jumlah_tanaman', type: 'integer', example: 50),
                                        new OA\Property(property: 'status', type: 'string', example: 'berjalan'),
                                        new OA\Property(property: 'hss', type: 'integer', example: 21)
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T14:30:00.000000Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T14:30:00.000000Z')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The image field is required. (and 1 more error)'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'image',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The image field is required.')
                                ),
                                new OA\Property(
                                    property: 'budidaya_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The budidaya_id field is required.')
                                )
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
        ]
    )]
    public function store(DiagnosisStoreRequest $request): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            $validated = $request->validated();
            $image = $request->file('image');

            // Generate random filename and store image
            $filename = Str::random(40) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('diagnosis', $filename, 'public');

            // Get full path for classification
            $fullPath = storage_path('app/public/' . $path);

            // Classify image using injected service
            $result = $this->diagnosisService->classify($fullPath);

            // Get recommendation from config
            $rekomendasi = config("sipentar.diagnosis_recommendations.{$result['hasil']}", 
                'Pemeriksaan selesai. Silakan konsultasikan dengan ahli untuk saran lebih lanjut.');

            // Create diagnosis record
            $diagnosis = Diagnosis::create([
                'user_id' => $validated['user_id'],
                'budidaya_id' => $validated['budidaya_id'],
                'image_path' => $path,
                'hasil' => $result['hasil'],
                'confidence' => $result['confidence'],
                'rekomendasi' => $rekomendasi,
                'tanggal_diagnosis' => now(),
            ]);

            // Load relationships
            $diagnosis->load(['user', 'budidaya']);

            DB::commit();

            return $this->successResponse(
                new DiagnosisResource($diagnosis),
                'Diagnosis berhasil dibuat.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded image if exists
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }

            return $this->errorResponse(
                'Terjadi kesalahan saat membuat diagnosis: ' . $e->getMessage(),
                500
            );
        }
    }

    #[OA\Get(
        path: '/api/v1/diagnosis/{id}',
        summary: 'Detail diagnosis kesehatan tanaman',
        security: [['bearerAuth' => []]],
        tags: ['Diagnosis'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data diagnosis berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data diagnosis berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'budidaya_id', type: 'integer', example: 2),
                                new OA\Property(property: 'image_url', type: 'string', example: 'http://localhost:8000/storage/diagnosis/abc123.jpg'),
                                new OA\Property(property: 'hasil', type: 'string', example: 'busuk_batang'),
                                new OA\Property(property: 'confidence', type: 'string', example: '87.45'),
                                new OA\Property(property: 'rekomendasi', type: 'string', example: 'Terdeteksi busuk batang. Segera isolasi tanaman yang terinfeksi...'),
                                new OA\Property(property: 'tanggal_diagnosis', type: 'string', format: 'date-time', example: '2026-11-22 14:30:00'),
                                new OA\Property(property: 'disclaimer', type: 'string', example: 'Hasil ini hanya informasi pendukung, bukan pengganti pemeriksaan langsung.'),
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                                        new OA\Property(property: 'username', type: 'string', example: 'budi_santoso'),
                                        new OA\Property(property: 'role', type: 'string', example: 'pegawai')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'budidaya',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                        new OA\Property(property: 'meja_id', type: 'integer', example: 3),
                                        new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date', example: '2026-11-01'),
                                        new OA\Property(property: 'jumlah_tanaman', type: 'integer', example: 50),
                                        new OA\Property(property: 'status', type: 'string', example: 'berjalan'),
                                        new OA\Property(property: 'hss', type: 'integer', example: 21)
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T14:30:00.000000Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T14:30:00.000000Z')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Diagnosis tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Diagnosis tidak ditemukan.')
                    ]
                )
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $diagnosis = Diagnosis::with(['user', 'budidaya'])->find($id);

        if (!$diagnosis) {
            return $this->errorResponse('Diagnosis tidak ditemukan.', 404);
        }

        return $this->successResponse(
            new DiagnosisResource($diagnosis),
            'Data diagnosis berhasil diambil.'
        );
    }
}
