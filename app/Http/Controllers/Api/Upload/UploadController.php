<?php

namespace App\Http\Controllers\Api\Upload;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadContentRequest;
use App\Http\Requests\UploadEpisodeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Repositories\Interfaces\EpisodeRepositoryInterface;
use App\Repositories\Interfaces\ContentRepositoryInterface;

class UploadController extends Controller
{
    protected EpisodeRepositoryInterface $episodeRepository;
    protected ContentRepositoryInterface $contentRepository;

    public function __construct(
        EpisodeRepositoryInterface $episodeRepository,
        ContentRepositoryInterface $contentRepository
    ) {
        $this->episodeRepository = $episodeRepository;
        $this->contentRepository = $contentRepository;
    }

    /**
     * Upload d'un fichier vidéo pour un contenu (film, série, etc.).
     *
     * Exigences dans la requête :
     * - video : le fichier vidéo à uploader.
     * - content_uuid : l'identifiant du contenu (film).
     *
     * @param UploadContentRequest $request
     * @return JsonResponse
     */
    public function uploadContent(UploadContentRequest $request): JsonResponse
    {
        Log::info('Upload pour un contenu');

        // Les règles sont déjà validées, vous pouvez directement utiliser $request->validated()
        $validated = $request->validated();

        // Récupération de l'UUID du contenu
        $contentUuid = $validated['content_uuid'];

        // Construction du chemin de stockage : hls/movies/{content_uuid}
        $basePath = "hls/movies/{$contentUuid}";

        // On passe l'UUID du contenu pour la mise à jour
        return $this->handleUpload($request, 'private', $basePath, 'contenu', $contentUuid);
    }

    /**
     * Upload d'un fichier vidéo pour un épisode.
     *
     * Exigences dans la requête :
     * - video : le fichier vidéo à uploader.
     * - episode_uuid : l'identifiant de l'épisode.
     *
     * La méthode récupère depuis la base les infos associées (saison et contenu) afin
     * de construire le chemin suivant : hls/series/{seriesUuid}/season_{seasonNumber}/{episode_uuid}
     *
     * @param UploadEpisodeRequest $request
     * @return JsonResponse
     */
    public function uploadEpisode(UploadEpisodeRequest $request): JsonResponse
    {
        Log::info('Upload pour un épisode');

        // Les règles sont déjà validées
        $validated = $request->validated();

        // Récupération de l'UUID de l'épisode
        $episodeUuid = $validated['episode_uuid'];

        // Récupération des informations de l'épisode depuis la base (avec saison et contenu)
        $episode = $this->episodeRepository->getEpisodeByUuid($episodeUuid);
        if (!$episode) {
            return response()->json([
                'error' => 'Épisode non trouvé',
            ], 404);
        }

        // Récupération des informations nécessaires pour construire le chemin
        // On suppose ici que la relation season et la relation content sont correctement définies.
        $seriesUuid   = $episode->season->content->uuid;
        $seasonNumber = $episode->season->season_number;

        // Construction du chemin de stockage : hls/series/{seriesUuid}/season_{seasonNumber}/{episode_uuid}
        $basePath = "hls/series/{$seriesUuid}/season_{$seasonNumber}/{$episodeUuid}";

        // On passe l'UUID de l'épisode pour la mise à jour
        return $this->handleUpload($request, 'private', $basePath, 'épisode', $episodeUuid);
    }

    /**
     * Méthode privée qui factorise le code commun pour l'upload d'une vidéo
     * et lance la conversion HLS avec FFmpeg.
     *
     * Une fois la conversion réussie, la méthode met à jour le champ is_uploaded
     * pour l'enregistrement correspondant via le repository approprié.
     *
     * @param Request $request
     * @param string  $diskName Nom du disque de stockage (ex: "private")
     * @param string  $basePath Chemin de stockage de base
     *                        (ex: "hls/movies/{content_uuid}" ou "hls/series/{seriesUuid}/season_{seasonNumber}/{episode_uuid}")
     * @param string  $typeMessage Chaîne descriptive pour les messages ("contenu" ou "épisode")
     * @param string  $recordUuid  L'UUID de l'enregistrement (contenu ou épisode)
     *
     * @return JsonResponse
     */
    private function handleUpload(Request $request, string $diskName, string $basePath, string $typeMessage, string $recordUuid): JsonResponse
    {
        // Règles de validation communes
        $rules = [
            'video' => 'required|file|mimetypes:video/mp4,video/x-matroska,video/avi|max:204800',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'error'   => 'Erreur de validation',
                'details' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('video');
            // Renommage du fichier pour éviter les conflits (timestamp + nom original)
            $filename = time() . '_' . $file->getClientOriginalName();

            // Création du dossier de stockage s'il n'existe pas déjà
            if (!Storage::disk($diskName)->exists($basePath)) {
                Storage::disk($diskName)->makeDirectory($basePath);
            }

            // Stockage initial du fichier uploadé
            $uploadedPath = $file->storeAs($basePath, $filename, $diskName);
            $uploadedFilePath = Storage::disk($diskName)->path($uploadedPath);

            // Chemin de sortie pour la conversion HLS (playlist master)
            $outputPlaylist = Storage::disk($diskName)->path($basePath . '/output.m3u8');

            // Commande FFmpeg pour convertir la vidéo en HLS
            $ffmpegCommand = "ffmpeg -i " . escapeshellarg($uploadedFilePath) .
                " -profile:v baseline -level 3.0 -start_number 0 -hls_time 10 -hls_list_size 0 -f hls " .
                escapeshellarg($outputPlaylist) . " 2>&1";

            Log::info("Commande FFmpeg : " . $ffmpegCommand);
            exec($ffmpegCommand, $ffmpegOutput, $returnCode);

            if ($returnCode !== 0) {
                $errorOutput = implode("\n", $ffmpegOutput);
                throw new Exception("Erreur de conversion HLS : " . $errorOutput);
            }

            // Suppression du fichier source uploadé
            Storage::disk($diskName)->delete($uploadedPath);

            // Mise à jour du champ is_uploaded en fonction du type d'enregistrement
            if ($typeMessage === 'épisode') {
                $this->episodeRepository->markEpisodeAsUploaded($recordUuid);
            } elseif ($typeMessage === 'contenu') {
                $this->contentRepository->markContentAsUploaded($recordUuid);
            }

            return response()->json([
                'message'      => "Fichier uploadé et converti en HLS pour le {$typeMessage} avec succès",
                'hls_playlist' => $basePath . '/output.m3u8'
            ], 200);
        } catch (Exception $e) {
            Log::error("Erreur lors de l'upload et conversion HLS pour le {$typeMessage} : " . $e->getMessage());

            $parentPath = dirname($basePath);       // "hls/series/{seriesUuid}/season_{seasonNumber}"
            $lastFolder = basename($basePath);        // "{episode_uuid}"
            $fullPathToDelete = $parentPath . '/' . $lastFolder;

            if (Storage::disk($diskName)->exists($fullPathToDelete)) {
                Storage::disk($diskName)->deleteDirectory($fullPathToDelete);
            }

            return response()->json([
                'error'   => "Erreur lors de l'upload ou de la conversion HLS pour le {$typeMessage}",
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
