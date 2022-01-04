<?php

namespace App\Http\Requests\Group_2;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        switch($this->method())
        {
            case 'POST':
                return [
                ];
                break;
            default: 
                break;
        }
    }
}
