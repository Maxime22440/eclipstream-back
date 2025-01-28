<?php

namespace App\Http\Controllers\Api\Streaming;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Episode;
use App\Services\StreamService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamingController extends Controller
{
    private StreamService $streamService;

    public function __construct(StreamService $streamService)
    {
        $this->streamService = $streamService;
    }

    public function streamEpisode($uuid): StreamedResponse
    {
        Log::info('Requête reçue pour le streaming de l\'épisode.', ['uuid' => $uuid]);

        $episode = Episode::where('uuid', $uuid)->firstOrFail();
        $filePath = Storage::disk('private')->path($episode->video_link);

        return $this->streamService->stream($filePath);
    }

    public function streamMovie($uuid): StreamedResponse
    {
        Log::info('Requête reçue pour le streaming du film.', ['uuid' => $uuid]);

        $movie = Content::where('uuid', $uuid)->firstOrFail();
        $filePath = Storage::disk('private')->path($movie->video_link);

        return $this->streamService->stream($filePath);
    }
}
