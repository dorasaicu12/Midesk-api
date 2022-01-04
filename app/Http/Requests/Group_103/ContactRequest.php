<?php

namespace App\Http\Requests\Group_103;

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
                switch ($groupid) {
                    case '196':
                        return [
                            'fullname' => 'required|max:50|min:1',
                            'phone' => 'required_without:ext_contact_id',
                            'ext_contact_id' => 'required_without:phone'
                        ];
                        break;
                    
                    default:
                        return [];
                        break;
                }
                break;
            case 'PUT':
                    return [];
                break;
            default: break;
        }
        return [];
    }
}
