<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class EventRequest extends FormRequest
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
                    'event_title' => 'required',
                    'event_type' => 'required',
                    'note' => 'required',
                    'remind_time' => 'required',
                    'handling_team' => 'required',
                    'handling_agent' => 'required',
                    'remind_type' => 'required',
                    'event_source' => 'required',
                    'event_source_id' => 'required'
                ];
                break;
            case 'PUT':
                return [
                ];
                break;
            default: break;
        }
        return [];
    }
}
