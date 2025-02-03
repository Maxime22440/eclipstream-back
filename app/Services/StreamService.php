<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Ancienne version du service de streaming
// Ce service n'est plus utilisé car le streaming se fait maintenant via HLS
class StreamService
{
    public function stream(string $filePath): StreamedResponse
    {
        if (session()->isStarted()) {
            session_write_close();
        }

        if (!file_exists($filePath)) {
            Log::error('Fichier vidéo introuvable.', ['path' => $filePath]);
            abort(404, 'Fichier non trouvé.');
        }

        $fileSize = filesize($filePath);
        $start = 0;
        $end = $fileSize - 1;

        // Gestion des requêtes Range
        if (isset($_SERVER['HTTP_RANGE'])) {
            Log::info('Requête avec Range.', ['HTTP_RANGE' => $_SERVER['HTTP_RANGE']]);
            [$unit, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);
            [$start, $end] = explode('-', $range);

            $start = intval($start);
            $end = ($end === '') ? $fileSize - 1 : intval($end);

            if ($start > $end || $end >= $fileSize) {
                abort(416, 'Invalid range');
            }
        }

        $length = $end - $start + 1;
        Log::info('Streaming partiel.', ['start' => $start, 'end' => $end, 'length' => $length]);

        // Ouvre le fichier pour le streaming
        $stream = fopen($filePath, 'rb');
        fseek($stream, $start);

        return response()->stream(function () use ($stream, $length) {
            $chunkSize = 8192; // Taille des chunks : 8 Ko
            while (!feof($stream) && $length > 0) {
                $data = fread($stream, min($chunkSize, $length));
                $length -= strlen($data);
                echo $data;
                flush(); // Envoie immédiatement les données au client
            }
            fclose($stream);
        }, 206, [ // Retourne un statut 206 Partial Content
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $length,
            'Content-Range' => "bytes $start-$end/$fileSize",
        ]);
    }
}
