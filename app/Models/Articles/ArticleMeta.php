<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class ArticleMeta extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    private const COMPANY_TYPE_TRANSLATIONS = [
        'tov' => 'admin.account.company.type_tov',
        'pat' => 'admin.account.company.type_pat',
        'prat' => 'admin.account.company.type_prat',
        'kp' => 'admin.account.company.type_kp',
        'dp' => 'admin.account.company.type_dp',
        'bo' => 'admin.account.company.type_bo',
        'go' => 'admin.account.company.type_go',
    ];

    protected $table = 'articles_meta';

    protected $fillable = [
        'article_id',
        'locale',
        'field',
        'value',
    ];

    protected $auditInclude = [
        'article_id',
        'locale',
        'field',
        'value',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public static function companyMetaRequestFields(): array
    {
        return [
            'edrpou' => 'company_edrpou',
            'website' => 'company_website',
            'social' => 'company_social',
            'phone' => 'company_phone',
            'director' => 'company_director',
            'position' => 'company_position',
            'company_type' => 'company_type',
        ];
    }

    public static function companyMetaLabels(): array
    {
        return [
            'edrpou' => __('admin.account.company.edrpou'),
            'website' => __('admin.account.company.website'),
            'social' => __('admin.account.company.social'),
            'phone' => __('admin.account.company.phone'),
            'director' => __('admin.account.company.director'),
            'position' => __('admin.account.company.position'),
            'company_type' => __('admin.account.company.type'),
        ];
    }

    public static function companyTypeSlugs(): array
    {
        return array_keys(self::COMPANY_TYPE_TRANSLATIONS);
    }

    public static function companyTypeOptions(): array
    {
        $options = [];

        foreach (self::companyTypeSlugs() as $slug) {
            $options[] = [
                'value' => $slug,
                'label' => __(self::COMPANY_TYPE_TRANSLATIONS[$slug]),
            ];
        }

        return $options;
    }

    public static function formatCompanyTypeValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (isset(self::COMPANY_TYPE_TRANSLATIONS[$value])) {
            return __(self::COMPANY_TYPE_TRANSLATIONS[$value]);
        }

        return $value;
    }
}
