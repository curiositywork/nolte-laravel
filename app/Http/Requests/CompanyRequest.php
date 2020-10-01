<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response as IlluminateResponse;

class CompanyRequest extends FormRequest
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
        switch ($this->method()) {
            case 'GET': {
                return [
                    'url' => [
                            'required',
                            'min:15',
                            'regex:/((http:|https:)\/\/)[^\/]+/'
                        ]
                ];
            }
            case 'POST': {
                return [
                        'url' => [
                            'required',
                            'unique:companies',
                            'min:15',
                            'regex:/((http:|https:)\/\/)[^\/]+/'
                        ],
                        'size' => 'required|in:micro,small,medium,large',
                        'industry' => 'required|in:apparel,banking_financial,electronics,food_groceries,goverment,others',
                        'business_type' => 'required|in:digital,ecommerce,both',
                    ];
            }
            default:
                break;
        }
    }
}
