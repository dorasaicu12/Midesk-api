<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class UserRequest extends FormRequest
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
                    'firstname' => 'required|max:50|min:2',
                    'lastname' => 'required|max:50|min:2',
                    'password' => 'required|confirmed|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                    'phone' => 'required|digits:10',
                    'email' => 'required|email',
                    'account_type' => 'required',
                    'perrmission' => 'required',
                    'role' => 'required'
                ];
                break;
            case 'PUT':
                return [
                    'password' => 'confirmed|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                ];
                break;
            default: break;
        }
        return [];
    }
}
