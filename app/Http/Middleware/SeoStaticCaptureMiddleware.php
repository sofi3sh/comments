<?php

namespace App\Http\Middleware;

use App\Jobs\GenerateStaticPublicFileJob;
use App\Services\Article\StaticFileService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;


//middleware (mirrors StaticCaptureMiddleware for SEO responses):
//  lock static:lock:{host}:{path} exists? -> skip
//  lock взят -> push job(host, path, content, lockKey)
//  если push упал -> lock удаляется
//
//job:
//  пишет .br на static-public (всегда перезаписывает)
//  finally удаляет lock


class SeoStaticCaptureMiddleware
{
    protected StaticFileService $staticFileService;

    public function __construct()
    {
        $this->staticFileService = app(StaticFileService::class);
    }

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * @throws \Throwable
     */
    public function terminate($request, $response): void
    {
        if (!$this->shouldHandle($request, $response)) {
            return;
        }

        $host = $request->getHost();
        $path = $request->path();

        $lockKey = "static:lock:{$host}:{$path}";

        if (!$this->staticFileService->acquireLock($lockKey)) {
            return;
        }

        try {
            Queue::push(new GenerateStaticPublicFileJob(
                $host,
                $path,
                $response->getContent(),
                $lockKey,
            ));
        } catch (\Throwable $e) {
            Redis::del($lockKey);

            throw $e;
        }
    }

    /**
     * @param Request $request
     * @param $response
     * @return bool
     */
    private function shouldHandle(Request $request, $response): bool
    {
        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && $request->getQueryString() === null
            && config('app.capture_enabled');
    }
}
