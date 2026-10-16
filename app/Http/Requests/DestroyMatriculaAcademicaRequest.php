<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\UsesActiveOfertaPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DestroyMatriculaAcademicaRequest extends FormRequest
{
    use UsesActiveOfertaPeriodo;

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [];
    }
}
