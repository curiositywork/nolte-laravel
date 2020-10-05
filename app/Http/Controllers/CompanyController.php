<?php

namespace App\Http\Controllers;

use App\Company;
use App\IndustryAverage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Services\CompanyService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response as IlluminateResponse;

class CompanyController extends Controller
{
    private $company;
    private $companyService;
    private $industryAverage;

    public function __construct()
    {
        $this->company = new Company;
        $this->companyService = resolve(CompanyService::class);
        $this->industryAverage = new IndustryAverage;
    }

    /**
     * Check if a company completes the onboarding process.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function check(Request $request)
    {
        $validator = Validator::make($request->all(), $this->company->rule);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->all());
        }

        $company = $this->company->findByUrl($request->url);

        return response()->json([
                'success' => TRUE,
                'completed' => !is_null($company),
            ], IlluminateResponse::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->company->createRule);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->all());
        }

        $this->company->create($request->all());

        return response()->json([
                'success' => TRUE,
                'message' => 'Successfully created',
            ], IlluminateResponse::HTTP_OK);
    }

    /**
     * Submit all components installed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function components(Request $request)
    {
        $validator = Validator::make($request->all(), $this->company->rule);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->all());
        }

        $company = $this->company->findByUrl($request->url);

        if (is_null($company)) {
            return $this->errorResponse($validator->errors()->all(), IlluminateResponse::HTTP_NOT_FOUND);
        }

        $company->deactivateComponents();

        try {
            foreach ($request->components as $type => $value) {
                if ($type == 'plugins') {
                    foreach ($value as &$plugin) {
                        $component = $this->companyService->getOrCreateComponent($plugin['slug'], 'plugin');
                        if (!is_null($component)) {
                            $company->addComponent($component->id, $plugin['version'], $plugin['active'], 'plugin');
                        }
                    }
                }
                else {
                    $component = $this->companyService->getOrCreateComponent($value['slug'], $type);
                    if (!is_null($component)) {
                        $company->addComponent($component->id, $value['version'], TRUE, $type);
                    }
                }
            }

            return response()->json([
                    'success' => TRUE,
                    'message' => 'Components successfully created',
                ], IlluminateResponse::HTTP_OK);
        }
        catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), IlluminateResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get company feedback.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function feedback(Request $request)
    {
        $validator = Validator::make($request->all(), $this->company->rule);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->all());
        }
        
        $company = $this->company->findByUrl($request->url);
        return response()->json([
                'success' => TRUE,
                'data' => $company->feedbackByStatus($request->status),
            ], IlluminateResponse::HTTP_OK);
    }

    /**
     * Get company report and industry average.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), $this->company->rule);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->all());
        }

        $company = $this->company->findByUrl($request->url);
        $insights = $company->insightsByWeek();

        return response()->json([
                'success' => TRUE,
                'average' => $this->industryAverage->findByIndustry($company->industry),
                'data' => $insights,
            ], IlluminateResponse::HTTP_OK);
    }

    private function errorResponse($error, $response = IlluminateResponse::HTTP_BAD_REQUEST)
    {
        return response()->json([
                'success' => FALSE,
                'error' => [ 'message' => $error ]
            ], $response
        );
    }
}
