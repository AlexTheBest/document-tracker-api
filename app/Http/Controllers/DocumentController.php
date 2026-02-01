<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Document::class, 'document');
    }

    /**
     * Display a listing of the user's documents
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Only return documents owned by the authenticated user
        $documents = $request->user()
            ->documents()
            ->with('owner')
            ->orderBy('expires_at', 'asc')
            ->get();

        return DocumentResource::collection($documents);
    }

    /**
     * Store a newly created document
     */
    public function store(StoreDocumentRequest $request): DocumentResource
    {
        try {
            $validated = $request->validated();

            // Store the uploaded PDF file
            $path = $request->file('file')->store('documents', 'local');

            // Create the document record
            $document = $request->user()->documents()->create([
                'name' => $validated['name'],
                'path' => $path,
                'expires_at' => $validated['expires_at'],
            ]);

            return DocumentResource::make($document->load('owner'));
        } catch (\Exception $e) {
            \Log::error('Document creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Display the specified document
     */
    public function show(Document $document): DocumentResource
    {
        return DocumentResource::make($document->load('owner'));
    }

    /**
     * Archive a document
     */
    public function archive(Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        if ($document->archived_at) {
            return response()->json([
                'message' => 'Document is already archived',
            ], 422);
        }

        $document->archive();

        return response()->json([
            'message' => 'Document archived successfully',
            'data' => DocumentResource::make($document),
        ]);
    }

    /**
     * Download the document file
     */
    public function download(Document $document)
    {
        $this->authorize('view', $document);

        if (!Storage::exists($document->path)) {
            return response()->json([
                'message' => 'File not found',
            ], 404);
        }

        return Storage::download($document->path, $document->name . '.pdf');
    }
}
