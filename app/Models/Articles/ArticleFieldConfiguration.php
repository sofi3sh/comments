<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class ArticleFieldConfiguration extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'article_type_id',
        'field_name',
        'is_required',
        'is_visible',
        'max_length',
        'min_length',
        'validation_rules',
        'position',
    ];

    protected $casts = [
        'article_type_id' => 'integer',
        'is_required' => 'boolean',
        'is_visible' => 'boolean',
        'validation_rules' => 'array',
    ];

    /**
     * Set validation rules attribute - convert string to array
     */
    public function setValidationRulesAttribute($value): void
    {
        if (is_string($value) && !empty($value)) {
            // Split by comma and trim each rule
            $rules = array_map('trim', explode(',', $value));
            $this->attributes['validation_rules'] = json_encode(array_filter($rules));
        } elseif (is_array($value)) {
            $this->attributes['validation_rules'] = json_encode($value);
        } else {
            $this->attributes['validation_rules'] = null;
        }
    }

    /**
     * Get validation rules as string for textarea
     */
    public function getValidationRulesStringAttribute(): ?string
    {
        if (!$this->validation_rules || !is_array($this->validation_rules)) {
            return null;
        }
        return implode(', ', $this->validation_rules);
    }

    /**
     * Get validation rules array for this field
     */
    public function getValidationRulesArray(): array
    {
        $rules = [];

        if ($this->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($this->max_length) {
            $rules[] = 'max:' . $this->max_length;
        }

        if ($this->min_length) {
            $rules[] = 'min:' . $this->min_length;
        }

        // Add custom validation rules
        if ($this->validation_rules && is_array($this->validation_rules)) {
            $rules = array_merge($rules, $this->validation_rules);
        }

        return $rules;
    }
}

