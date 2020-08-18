<?php

namespace App\Http\Controllers;

use App\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response as IlluminateResponse;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'url' => [
                    'required',
                    'unique:companies',
                    'min:15',
                    'regex:/((http:|https:)\/\/)[^\/]+/',
                ],
                'size' => 'required|in:micro,small,medium,large',
                'industry' => 'required|in:apparel,banking_financial,electronics,food_groceries,goverment,others',
                'business_type' => 'required|in:digital,ecommerce,both',
            ]
        );

        if ($validator->fails()) {
            return response()->json(
                [
                    'success' => FALSE,
                    'error' => [
                        'code' => 100,
                        'messages' => $validator->errors()->all()
                    ]
                ], IlluminateResponse::HTTP_BAD_REQUEST
            );
        }

        $company = new Company;

        $company->url = $request->url;
        $company->size = $request->size;
        $company->industry = $request->industry;
        $company->business_type = $request->business_type;
        $company->save();

        return response()->json(
            [
                'success' => TRUE,
                'message' => 'Successfully created',
                'data' => $company
            ], IlluminateResponse::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        //
    }
}
