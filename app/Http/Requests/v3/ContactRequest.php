<?php

namespace App\Http\Requests\v3;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class ContactRequest extends FormRequest
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
        $groupid   = auth::user()->groupid;
        switch($this->method())
        {
            case 'POST':
                return [];
                break;
            case 'PUT':
                    return [];
                break;
            default: break;
        }
        return [];
    }
}
