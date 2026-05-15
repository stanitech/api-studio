<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProxyController extends Controller
{
    /**
     * POST /api/proxy/run
     * Forwards the request server-side (no CORS) with all headers preserved.
     */
    public function run(Request $request)
    {
        $request->validate([
            'method'  => 'required|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'url'     => 'required|string',   // allow non-standard URLs
            'headers' => 'nullable|array',
            'body'    => 'nullable|string',
        ]);

        $method  = strtoupper($request->input('method'));
        $url     = $request->input('url');
        $body    = $request->input('body');

        // Build clean headers — strip browser/proxy-specific ones
        $rawHeaders = $request->input('headers', []);
        $headers    = [];
        $skip       = ['host','x-csrf-token','cookie','origin','referer','x-forwarded-for','x-forwarded-host','x-forwarded-proto'];

        foreach ($rawHeaders as $key => $value) {
            if (!in_array(strtolower($key), $skip) && $value !== '' && $value !== null) {
                // Skip placeholder "(Global Bearer)" value — real token is injected below
                if (strtolower($key) === 'authorization' && str_contains($value, '(Global Bearer)')) {
                    continue;
                }
                $headers[$key] = $value;
            }
        }

        // Determine content type
        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? 'application/json';

        try {
            // Build the HTTP client with ALL headers attached first
            $client = Http::withHeaders($headers)
                ->timeout(30)
                ->withoutVerifying();

            $response = match ($method) {
                'GET'    => $client->get($url),
                'DELETE' => $client->delete($url),
                'HEAD'   => $client->head($url),
                // For body methods, use send() to keep headers intact
                'POST'   => $client->withBody($body ?? '', $contentType)->post($url),
                'PUT'    => $client->withBody($body ?? '', $contentType)->put($url),
                'PATCH'  => $client->withBody($body ?? '', $contentType)->patch($url),
                default  => $client->get($url),
            };

            return response()->json([
                'status'     => $response->status(),
                'statusText' => $this->statusText($response->status()),
                'headers'    => $response->headers(),
                'body'       => $response->body(),
                'ok'         => $response->successful(),
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'error'   => 'connection_failed',
                'message' => 'Could not connect: ' . $e->getMessage(),
            ], 502);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'proxy_error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function statusText(int $code): string
    {
        return [
            200=>'OK', 201=>'Created', 202=>'Accepted', 204=>'No Content',
            301=>'Moved Permanently', 302=>'Found', 304=>'Not Modified',
            400=>'Bad Request', 401=>'Unauthorized', 403=>'Forbidden',
            404=>'Not Found', 405=>'Method Not Allowed', 409=>'Conflict',
            422=>'Unprocessable Entity', 429=>'Too Many Requests',
            500=>'Internal Server Error', 502=>'Bad Gateway', 503=>'Service Unavailable',
        ][$code] ?? 'Unknown';
    }
}
