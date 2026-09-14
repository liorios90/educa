<?php

namespace App\Http\Requests;

use App\Crud\CrudRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertCrudRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $registry = $this->container->make(CrudRegistry::class);
        $definition = $registry->get($registry->slugFromRouteName($this->route()?->getName()));
        $record = $this->route('record');

        return $definition->validationRules($record !== null ? (int) $record : null);
    }
}
