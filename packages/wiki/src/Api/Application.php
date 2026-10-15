<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Api;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Persistence\Codec\Codecs;
use Lorekeeper\Persistence\Store\FactStore;
use Lorekeeper\Wiki\Kind;
use Lorekeeper\Wiki\Kinds;
use Lorekeeper\Wiki\Links;
use Lorekeeper\Wiki\Names;

final readonly class Application
{
    private Links $links;

    private Serializer $serializer;

    private Queries $queries;

    public function __construct(FactStore $store, Codecs $codecs)
    {
        $names = new Names($store);
        $this->links = new Links($codecs);
        $this->serializer = new Serializer($codecs, $names);
        $this->queries = new Queries($store, $this->serializer, $names);
    }

    public function handle(string $path): JsonResponse
    {
        $path = '/' . trim(substr($path, strlen('/api')), characters: '/');

        return match (true) {
            $path === '/overview' => new JsonResponse(200, $this->queries->overview()),
            $path === '/subjects' => new JsonResponse(200, $this->queries->searchIndex()),
            $path === '/atlas' => new JsonResponse(200, $this->queries->atlas()),
            default => $this->routeKind($path),
        };
    }

    private function routeKind(string $path): JsonResponse
    {
        $parts = explode('/', trim($path, characters: '/'), limit: 2);
        $kind = Kinds::byKey($parts[0]);

        if ($kind->isNone()) {
            return self::notFound();
        }

        if (count($parts) === 1) {
            return new JsonResponse(200, $this->queries->listing($kind->unwrap()));
        }

        return $this->links->parse($path)->proceed(
            fn(Subject $subject): JsonResponse => $this->detail($subject, $kind->unwrap()),
            self::notFound(...),
        );
    }

    private function detail(Subject $subject, Kind $kind): JsonResponse
    {
        $detail = $this->queries->detail($subject, $kind);

        return $detail['facts'] === [] && $detail['references'] === []
            ? self::notFound()
            : new JsonResponse(200, $detail);
    }

    private static function notFound(): JsonResponse
    {
        return new JsonResponse(404, ['error' => 'Not found']);
    }
}
