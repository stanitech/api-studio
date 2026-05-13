<?php

namespace App\Services;

use Illuminate\Support\Str;

class PostmanParserService
{
    /**
     * Recursively parse Postman collection items into a flat array of endpoints.
     */
    public function parse(array $items, array $breadcrumb = []): array
    {
        $endpoints = [];

        foreach ($items as $item) {
            $name = $item['name'] ?? 'Unnamed';

            if (isset($item['item'])) {
                // Folder — recurse
                $endpoints = array_merge(
                    $endpoints,
                    $this->parse($item['item'], array_merge($breadcrumb, [$name]))
                );
            } else {
                // Leaf endpoint
                $endpoints[] = $this->parseEndpoint($item, $breadcrumb);
            }
        }

        return $endpoints;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function parseEndpoint(array $item, array $breadcrumb): array
    {
        $req    = $item['request'] ?? [];
        $urlObj = $req['url'] ?? [];
        $rawUrl = is_array($urlObj) ? ($urlObj['raw'] ?? '') : (string) $urlObj;

        return [
            'id'          => (string) Str::uuid(),
            'name'        => $item['name'] ?? 'Unnamed',
            'group'       => $breadcrumb[0] ?? 'General',
            'breadcrumb'  => $breadcrumb,
            'method'      => strtoupper($req['method'] ?? 'GET'),
            'url'         => $rawUrl,
            'description' => $req['description'] ?? '',
            'auth'        => $this->parseAuth($req['auth'] ?? []),
            'headers'     => $this->parseKeyValues($req['header'] ?? []),
            'query'       => $this->parseQuery($urlObj),
            'path_vars'   => $this->parseKeyValues($urlObj['variable'] ?? []),
            'body'        => $this->parseBody($req['body'] ?? []),
            'responses'   => $this->parseResponses($item['response'] ?? []),
            'ai_summary'  => null,
            'created_at'  => now()->toIso8601String(),
            'updated_at'  => now()->toIso8601String(),
        ];
    }

    private function parseAuth(array $auth): array
    {
        if (empty($auth)) return ['type' => 'noauth'];

        $type   = $auth['type'] ?? 'noauth';
        $result = ['type' => $type];

        if ($type === 'bearer') {
            foreach ($auth['bearer'] ?? [] as $b) {
                if ($b['key'] === 'token') {
                    $result['token'] = $b['value'] ?? '';
                }
            }
        } elseif ($type === 'basic') {
            foreach ($auth['basic'] ?? [] as $b) {
                $result[$b['key']] = $b['value'] ?? '';
            }
        } elseif ($type === 'apikey') {
            foreach ($auth['apikey'] ?? [] as $b) {
                $result[$b['key']] = $b['value'] ?? '';
            }
        }

        return $result;
    }

    private function parseKeyValues(array $items): array
    {
        return array_map(fn($i) => [
            'key'         => $i['key'] ?? '',
            'value'       => $i['value'] ?? '',
            'description' => $i['description'] ?? '',
            'type'        => $i['type'] ?? 'text',
            'disabled'    => $i['disabled'] ?? false,
        ], $items);
    }

    private function parseQuery(array|string $urlObj): array
    {
        if (!is_array($urlObj)) return [];

        return $this->parseKeyValues($urlObj['query'] ?? []);
    }

    private function parseBody(array $body): array
    {
        if (empty($body)) return [];

        $mode   = $body['mode'] ?? '';
        $result = ['mode' => $mode];

        switch ($mode) {
            case 'raw':
                $result['raw']     = $body['raw'] ?? '';
                $result['options'] = $body['options'] ?? [];
                break;

            case 'urlencoded':
                $result['urlencoded'] = $this->parseKeyValues($body['urlencoded'] ?? []);
                break;

            case 'formdata':
                $result['formdata'] = $this->parseKeyValues($body['formdata'] ?? []);
                break;

            case 'graphql':
                $result['graphql'] = $body['graphql'] ?? [];
                break;
        }

        return $result;
    }

    private function parseResponses(array $responses): array
    {
        return array_map(fn($r) => [
            'name'   => $r['name'] ?? '',
            'code'   => $r['code'] ?? 200,
            'status' => $r['status'] ?? '',
            'body'   => $r['body'] ?? '',
            'headers'=> $r['header'] ?? [],
        ], $responses);
    }
}
