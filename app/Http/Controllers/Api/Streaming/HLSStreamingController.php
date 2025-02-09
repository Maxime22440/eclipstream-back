<?php

namespace App\Http\Controllers\Api\Streaming;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Repositories\Interfaces\ContentRepositoryInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HLSStreamingController extends Controller
{
    // Durée d'expiration des URLs signées en minutes (ici 190 minutes)
    private const SIGNED_URL_EXPIRATION = 190;
    protected ContentRepositoryInterface $contentRepository;

    public function __construct(ContentRepositoryInterface $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    /**
     * Vérifie que l'utilisateur authentifié correspond au user_id fourni dans l'URL.
     *
     * @param Request $request
     * @return void L'ID de l'utilisateur authentifié.
     *
     * @throws HttpException (403) si non autorisé.
     */
    private function checkUserAuthorization(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Accès non autorisé : utilisateur non authentifié.');
        }
        $signedUserId = $request->query('user_id');
        if ((string)$signedUserId !== (string)$user->id) {
            abort(403, 'Accès non autorisé.');
        }
    }

    /**
     * Récupère le disque de stockage "private".
     *
     * @return Filesystem
     */
    private function getDisk(): Filesystem
    {
        return Storage::disk('private');
    }

    /**
     * Génère une URL signée pour un segment HLS.
     *
     * @param string $routeName Nom de la route à utiliser pour générer l'URL signée.
     * @param array  $parameters Paramètres à inclure dans l'URL.
     * @return string URL signée.
     */
    private function generateSignedSegmentUrl(string $routeName, array $parameters): string
    {
        return URL::temporarySignedRoute(
            $routeName,
            now()->addMinutes(self::SIGNED_URL_EXPIRATION),
            $parameters
        );
    }

    /**
     * Stream les fichiers HLS d'un film.
     *
     * @param Request $request
     * @param string $movieUuid
     * @param string $filename
     * @return Response
     */
    public function streamMovieHLS(Request $request, string $movieUuid, string $filename): Response
    {
        // Vérification de l'autorisation utilisateur
        $this->checkUserAuthorization($request);

        Log::info('Requête reçue pour le streaming HLS du film.', [
            'movieUuid' => $movieUuid,
            'filename'  => $filename,
        ]);

        $disk = $this->getDisk();
        $filePath = "hls/movies/{$movieUuid}/{$filename}";

        if (!$disk->exists($filePath)) {
            abort(404, 'Fichier HLS du film non trouvé.');
        }

        $headers = [
            'Content-Type'        => ($filename === 'output.m3u8') ? 'application/vnd.apple.mpegurl' : 'video/MP2T',
            'Content-Disposition' => 'inline',
        ];

        return response()->file($disk->path($filePath), $headers);
    }

    /**
     * Stream la playlist HLS d'un film en y injectant dynamiquement les URL signées pour chaque segment.
     *
     * @param Request $request
     * @param string $movieUuid
     * @return Response
     */
    public function streamSignedPlaylistForMovie(Request $request, string $movieUuid): Response
    {
        Log::info('Requête pour la playlist HLS signée du film.', ['movieUuid' => $movieUuid]);

        $disk = $this->getDisk();
        $filePath = "hls/movies/{$movieUuid}/output.m3u8";

        if (!$disk->exists($filePath)) {
            abort(404, 'Playlist HLS du film non trouvée.');
        }

        $this->contentRepository->incrementViews($movieUuid);

        $playlistContent = $disk->get($filePath);
        $lines = preg_split('/\r\n|\r|\n/', $playlistContent);
        $userId = $request->user()->id;

        // Transformation des lignes : pour chaque segment .ts, générer une URL signée
        $lines = array_map(function ($line) use ($userId, $movieUuid) {
            $trimmedLine = trim($line);
            if (str_ends_with($trimmedLine, '.ts')) {
                $signedSegmentUrl = $this->generateSignedSegmentUrl('stream.hls.movie', [
                    'movieUuid' => $movieUuid,
                    'filename'  => $trimmedLine,
                    'user_id'   => $userId,
                ]);
                Log::info('Segment URL générée pour le film', ['segmentUrl' => $signedSegmentUrl]);
                return $signedSegmentUrl;
            }
            return $line;
        }, $lines);

        $modifiedPlaylist = implode("\n", $lines);

        return response($modifiedPlaylist, 200, [
            'Content-Type'  => 'application/vnd.apple.mpegurl',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Stream les fichiers HLS d'un épisode de série.
     *
     * @param Request $request
     * @param string $seriesUuid
     * @param string $season
     * @param string $episodeUuid
     * @param string $filename
     * @return Response
     */
    public function streamEpisodeHLS(Request $request, string $seriesUuid, string $season, string $episodeUuid, string $filename): Response
    {
        $this->checkUserAuthorization($request);

        Log::info('Requête reçue pour le streaming HLS de l\'épisode de série.', [
            'seriesUuid'  => $seriesUuid,
            'season'      => $season,
            'episodeUuid' => $episodeUuid,
            'filename'    => $filename,
        ]);

        $disk = $this->getDisk();
        $filePath = "hls/series/{$seriesUuid}/{$season}/{$episodeUuid}/{$filename}";

        if (!$disk->exists($filePath)) {
            abort(404, 'Fichier HLS de l\'épisode de série non trouvé.');
        }

        $headers = [
            'Content-Type'        => ($filename === 'output.m3u8') ? 'application/vnd.apple.mpegurl' : 'video/MP2T',
            'Content-Disposition' => 'inline',
        ];

        return response()->file($disk->path($filePath), $headers);
    }

    /**
     * Sert la playlist HLS en y injectant dynamiquement les URL signées pour chaque segment (pour un épisode).
     *
     * @param Request $request
     * @param string $episodeUuid
     * @return Response
     */
    public function streamSignedPlaylistForEpisode(Request $request, string $episodeUuid): Response
    {
        Log::info('Requête pour la playlist HLS signée de l\'épisode.', ['episodeUuid' => $episodeUuid]);

        $cacheKey = 'episode_info_' . $episodeUuid;
        $episodeData = Cache::remember($cacheKey, 600, function () use ($episodeUuid) {
            return Episode::with('season.content')->where('uuid', $episodeUuid)->firstOrFail();
        });

        $seriesUuid   = $episodeData->season->content->uuid;
        $seasonNumber = $episodeData->season->season_number;
        $filePath = "hls/series/{$seriesUuid}/season_{$seasonNumber}/{$episodeUuid}/output.m3u8";

        $disk = $this->getDisk();
        if (!$disk->exists($filePath)) {
            abort(404, 'Playlist HLS non trouvée.');
        }

        $this->contentRepository->incrementViews($episodeData->season->content->uuid);

        $playlistContent = $disk->get($filePath);
        $lines = preg_split('/\r\n|\r|\n/', $playlistContent);
        $userId = $request->user()->id;

        $lines = array_map(function ($line) use ($seriesUuid, $seasonNumber, $episodeUuid, $userId) {
            $trimmedLine = trim($line);
            if (str_ends_with($trimmedLine, '.ts')) {
                $signedSegmentUrl = URL::temporarySignedRoute(
                    'stream.hls.episode',
                    now()->addMinutes(self::SIGNED_URL_EXPIRATION),
                    [
                        'seriesUuid'  => $seriesUuid,
                        'season'      => 'season_' . $seasonNumber,
                        'episodeUuid' => $episodeUuid,
                        'filename'    => $trimmedLine,
                        'user_id'     => $userId,
                    ]
                );
                Log::info('Segment URL générée pour l\'épisode', ['segmentUrl' => $signedSegmentUrl]);
                return $signedSegmentUrl;
            }
            return $line;
        }, $lines);

        $modifiedPlaylist = implode("\n", $lines);

        return response($modifiedPlaylist, 200, [
            'Content-Type'  => 'application/vnd.apple.mpegurl',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
