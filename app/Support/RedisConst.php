<?php

namespace App\Support;

class RedisConst
{
    public const STREAM = 'v_stream';
    public const PROCESSING_STREAM = 'v_stream_processing';
    public const ARTICLES = 'a:views';
    public const CRON_VIEWS_RUN = 'cron:views:running';
}