<?php

namespace App\Models\Articles\Translate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class MarkerTranslation extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'id',
        'marker_id',
        'locale',
        'name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'marker_id' => 'integer',
    ];

    /**
     * Get the marker that owns the translation
     */
    public function marker()
    {
        return $this->belongsTo(\App\Models\Articles\Marker::class);
    }

    /**
     * Get the fields config for the marker translation
     * @return array
     */
    public static function getFieldsConfig(): array
    {
        return [
            'name' => [
                'label' => __('marker-translation.fields.name'),
                'type' => 'text',
            ],
        ];
    }

    /**
     * Get the field prefix for marker translations
     * @return string
     */
    public static function getFieldPrefix(): string
    {
        return 'marker-translation';
    }
}

