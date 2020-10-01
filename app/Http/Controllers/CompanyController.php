<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Audit;
use App\Company;
use App\Insight;
use App\Component;
use App\Vulnerability;
use App\IndustryAverage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Services\CompanyService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response as IlluminateResponse;

class CompanyController extends Controller
{
    const WORDPRESS = 'wordpress';
    const PLUGINS  = 'plugins';

    private $company;
    private $component;
    private $vulnerability;
    private $industryAverage;

    public function __construct()
    {
        $this->company = new Company;
        $this->companyService = new CompanyService;
        $this->industryAverage = new IndustryAverage;
    }

    /**
     * Check if a company completes the onboarding process.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function onboardingCompleted(Request $request)
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
                if ($type == static::PLUGINS) {
                    foreach ($value as &$plugin) {
                        $type = 'plugin';
                        $component = $this->companyService->getOrCreateComponent($plugin['slug'], $type);
                        $version = $plugin['version'];
                        $active = $plugin['active'];
                    }
                }
                else {
                    $component = $this->companyService->getOrCreateComponent($value['slug'], $type);
                    $version = $value['version'];
                    $active = TRUE;
                }
                if (!is_null($component)) {
                    $company->addComponent(
                        $component->id,
                        $version,
                        $active,
                        $type
                    );
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


    public function feedback(Request $request)
    {
        $validator = Validator::make($request->all(), $this->company->rule);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->all());
        }

        return response()->json([
                'success' => TRUE,
                'data' => $company->feedbackByStatus($request->url, $request->status),
            ], IlluminateResponse::HTTP_OK);
    }

    public function report(Request $request)
    {
        $company = $this->company->findByUrl($request->url);
        $insights = $company->insightsByWeek();

        return response()->json([
                'success' => TRUE,
                'average' => $this->industryAverage->findByIndustry($company->industry),
                'data' => $insights,
            ], IlluminateResponse::HTTP_OK);
    }

    private function vulnReport($path, $slug)
    {
        $url = getenv('WP_VULN_BASE_URL'). '/' .$path. '/' .$slug;
        $response = Http::withHeaders(['Authorization' => 'Token token=' . getenv('WP_VULN_TOKEN')])->get($url);
        if ($response->ok()) {
            return $response->json();
        }
        return null;
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
