<?php

namespace App\Crud;

use Illuminate\Database\Eloquent\Model;

readonly class CrudField
{
    /**
     * @param  list<mixed>  $rules
     * @param  class-string<Model>|null  $relatedModel
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $type = 'text',
        public bool $list = true,
        public array $rules = [],
        public bool $unique = false,
        public ?string $relatedModel = null,
        public string $optionLabel = 'nombre',
        public ?string $relation = null,
    ) {}
}
