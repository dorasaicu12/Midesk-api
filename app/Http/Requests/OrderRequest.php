<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class OrderRequest extends FormRequest
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
        $rules = [];
        switch($this->method())
        {
            case 'POST':
                if (is_array(request('contact'))) {
                    $rules["contact.fullname"]  = "required|max:50|min:3";
                    $rules["contact.phone"]  = "required_without:contact.email";
                    $rules["contact.email"]  = "required_without:contact.phone";
                }

                if (is_array(request("customer"))) {
                    $rules["customer.fullname"]  = "required|max:50|min:3";
                    $rules["customer.phone"]  = "required_without:customer.email";
                    $rules["customer.email"]  = "required_without:customer.phone";
                }
                $rules["product.*.product_code"]  = "required";
                $rules["product.*.product_name"]  = "required";

                return $rules;
                
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
