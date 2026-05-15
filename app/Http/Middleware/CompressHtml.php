<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressHtml
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // /** @var Response $response */
        $response = $next($request);

        // Only compress successful HTML pages, don't break file downloads or API JSON
        if ($response instanceof \Illuminate\Http\Response  && str_contains($response->headers->get('Content-Type') ?? '', 'text/html')) {
            $html = $response->getContent();

            $filters = [
                '/<!--([^\[|<>].*?)-->/s' => '',  // Remove standard HTML comments
                '/(?:\r\n|\r|\n)/'        => '',  // Strip all newlines and line breaks
                '/(\t)/'                  => '',  // Strip all tab indentations
                '/(\s)+/'                 => ' ', // Collapse multiple spaces into one space
            ];

            $compressedHtml = preg_replace(array_keys($filters), array_values($filters), $html);

            if ($compressedHtml !== null) {
                $response->setContent($compressedHtml);
            }
        }

        return $response;
    }
}
