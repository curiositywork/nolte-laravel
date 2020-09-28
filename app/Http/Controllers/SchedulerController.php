<?php

namespace App\Http\Controllers;

use App\Company;
use App\IndustryAverage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response as IlluminateResponse;

class SchedulerController extends Controller
{
    const INDUSTRY_AVERAGES_BASE = [
        'apparel' => 62,
        'banking_financial' => 66,
        'electronics' => 74,
        'food_groceries' => 74,
        'goverment' => 65,
        'others' => 69
    ];

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function industryAverage(Request $request)
    {
        if ( !app()->environment('development') )
        {
            if ( !$request->hasHeader( 'X-Appengine-Cron' ) )
            {
                return response()->json(
                    [
                        'success' => FALSE,
                        'error' => [
                            'code' => 100,
                            'messages' => 'Unauthorized'
                        ]
                    ], IlluminateResponse::HTTP_UNAUTHORIZED
                );
            }
        }

        foreach( static::INDUSTRY_AVERAGES_BASE as $key => $value )
        {   
            $industryAvg = [ $value ];
            
            $industryAverage = IndustryAverage::firstOrNew( [ 'industry' => $key ] );
            $companies = Company::where('industry', $key)->get();
            foreach ( $companies as &$company )
            {
                $insight = $company->insights()->latest()->first();
                if ( !is_null( $insight ) )
                {
                    array_push( $industryAvg, $insight->general );
                }
            }
            $industryAverage->value = intval(array_sum($industryAvg)/count($industryAvg));
            $industryAverage->save();
        }

        return response()->json(
            [
                'success' => TRUE
            ], IlluminateResponse::HTTP_OK);
    }
}
