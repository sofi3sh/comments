<?php

namespace App\SEO\Contracts;

use App\SEO\Data\SeoPage;

interface SeoSource
{
    public function toSeoPage(): SeoPage;
}
