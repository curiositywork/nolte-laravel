<?php

namespace App\Http\Controllers;

use App\Company;
use App\IndustryAverage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Services\InsightsService;
use Illuminate\Http\Response as IlluminateResponse;

class SchedulerController extends Controller
{
    private $company;
    private $insightService;
    private $industryAverage;

    public function __construct()
    {
        $this->company = new Company;
        $this->insightService = resolve(InsightsService::class);
        $this->industryAverage = new IndustryAverage;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function industryAverage(Request $request)
    {
        foreach($this->industryAverage->industries as $industry => $value) {
            $industryAvg = [$value];
            $industryAverage = $this->industryAverage->firstOrNew(['industry' => $industry]);
            $companies = $this->company->getByIndustry($industry);
            foreach ($companies as &$company) {
                $insight = $company->insights()->latest()->first();
                if (!is_null( $insight)) {
                    array_push( $industryAvg, $insight->general );
                }
            }
            $industryAverage->value = intval(array_sum($industryAvg)/count($industryAvg));
            $industryAverage->save();
        }

        return response()->json([
                'success' => TRUE
            ], IlluminateResponse::HTTP_OK);
    }

    public function insights(Request $request)
    {
        $companies = $this->company->all();
        $this->insightService->generateFeedback($companies);

        return response()->json([
                'success' => TRUE,
            ], IlluminateResponse::HTTP_OK);
    }
}
