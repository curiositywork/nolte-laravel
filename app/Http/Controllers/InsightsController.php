<?php

namespace App\Http\Controllers;

use App\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Services\InsightsService;
use Illuminate\Http\Response as IlluminateResponse;

class InsightsController extends Controller
{
    private $company;
    private $insightService;

    public function __construct()
    {
        $this->company = new Company;
        $this->insightService = resolve(InsightsService::class);
    }

    public function insights(Request $request)
    {
        $companies = $this->company->whereUrl($request->url)->get();
        $general = $this->insightService->generateFeedback($companies);

        return response()->json([
                'success' => TRUE,
                'general' => $general,
            ], IlluminateResponse::HTTP_OK);
    }
}
