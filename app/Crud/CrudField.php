<?php

namespace App\Crud;

readonly class CrudField
{
    /**
     * @param  list<mixed>  $rules
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $type = 'text',
        public bool $list = true,
        public array $rules = [],
        public bool $unique = false,
    ) {}
}
