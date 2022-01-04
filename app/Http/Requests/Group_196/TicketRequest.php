<?php

namespace App\Http\Requests\Group_196;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class TicketRequest extends FormRequest
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
        if (array_key_exists(0, (request()->all()))) {
            $result = [
                '*.title'    => 'required',
                '*.content'  => 'required',
            ];
        }else{
            $result = [
                'title'    => 'required',
                'content'  => 'required',
            ];
        }
        if (is_array(request('contact'))) {
            switch($this->method())
            {   
                case 'POST':
                    $result['contact.name']  = 'required';
                    $result['contact.phone'] = 'required_without:contact.email';
                    $result['contact.email'] = 'required_without:contact.phone';
                    break;
                case 'PUT':
                    $result['contact.name']  = 'required';
                    $result['contact.phone'] = 'required_without:contact.email';
                    $result['contact.email'] = 'required_without:contact.phone';
                    break;
                default: break;
            }
        }elseif (request('contact') != ''){
            $result['contact_id']  = 'required';
        }
        return $result;
    }
}
