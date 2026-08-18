<?php

namespace App\Http\Middleware;

use App\Jobs\GenerateStaticArticleJob;
use App\Services\Article\StaticFileService;
use App\Services\Settings\SettingsService;
use App\Support\LogChannels;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;


//middleware:
//  lock static:lock:{host}:{uri} exists? -> skip
//  lock взят -> push job(uri, articleData, lockKey)
//  если push упал -> lock удаляется
//
//job/service:
//  пишет html/rest (всегда перезаписывает)
//  finally удаляет lock
//
// Проверки существования файла нет: диски S3, каждая была бы сетевым HEAD,
// причём почти всегда отрицательным — nginx доходит до Laravel только когда
// объекта нет. От дублей защищает Redis-lock.


class StaticCaptureMiddleware
{
    protected StaticFileService $staticFileService;
    protected SettingsService $settingsService;
    protected bool $toLog;

    public function __construct()
    {
        $this->staticFileService = app(StaticFileService::class);
        $this->settingsService = app(SettingsService::class);
        $this->toLog = (bool) config('logging.test.info', false);
    }
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    /**
     * @throws \Throwable
     */
    public function terminate($request, $response)
    {
        //todo replace with admin static settings

        if (! config('app.capture_enabled')) {
            return $response;
        }

        $trace = uniqid('static_', true);
        $this->toLog && logTo(LogChannels::STATIC)->info("[$trace] enter");

        if (!$this->shouldHandle($request, $response)) {
            $this->toLog && logTo(LogChannels::STATIC)->info("[$trace] skip: shouldHandle=false");
            return $response;
        }

        $pageData = $this->staticFileService->extractPageData($request);

        if (!$pageData) {
            return $response;
        }

        $uri = $request->path();

        $lockKey = "static:lock:{$pageData->host}:{$uri}";
        if (!$this->staticFileService->acquireLock($lockKey)) {
            $this->toLog && logTo(LogChannels::STATIC)->info("[$trace] skip: lock exists", ['lockKey' => $lockKey]);
            return $response;
        }

        $pageData->html = $response->getContent();

        try {
            Queue::push(new GenerateStaticArticleJob($uri, $pageData, $lockKey));
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
            && !$request->ajax()
            && $response->getStatusCode() === 200
            && $request->getQueryString() === null;
//            && (bool) $this->settingsService->get('static.mode');  // todo use if isset admin static settings
    }
}
