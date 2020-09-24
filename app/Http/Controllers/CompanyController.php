<?php

namespace App\Http\Controllers;

use Exception;
use App\Audit;
use App\Company;
use App\Insight;
use App\Customer;
use App\Feedback;
use App\Component;
use App\Vulnerability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response as IlluminateResponse;

class CompanyController extends Controller
{
    const WORDPRESS = 'wordpress';
    const PLUGINS  = 'plugins';

    public function onboardingCompleted(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'url' => [
                    'required',
                    'min:15',
                    'regex:/((http:|https:)\/\/)[^\/]+/'
                ]
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

        $company = Company::where('url', $request->url)->first();

        if (is_null($company))
        {
            return response()->json(
                [
                    'success' => TRUE,
                    'completed' => FALSE,
                ], IlluminateResponse::HTTP_OK);
        }

        return response()->json(
            [
                'success' => TRUE,
                'completed' => TRUE,
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
        $validator = Validator::make(
            $request->all(),
            [
                'url' => [
                    'required',
                    'unique:companies',
                    'min:15',
                    'regex:/((http:|https:)\/\/)[^\/]+/'
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
            ], IlluminateResponse::HTTP_OK);
    }

    public function components(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'url' => [
                    'required',
                    'min:15',
                    'regex:/((http:|https:)\/\/)[^\/]+/'
                ],
                'components' => 'required'
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

        $company = Company::where('url', $request->url)->first();

        if (is_null($company))
        {
            return response()->json(
                [
                    'success' => FALSE,
                    'error' => [
                        'code' => 100,
                        'messages' => 'Company not found'
                    ]
                ], IlluminateResponse::HTTP_NOT_FOUND
            );
        }

        $company->deactivateComponents();

        try {
            foreach ($request->components as $type => $value)
            {
                if ($type == static::PLUGINS)
                {
                    foreach ($value as &$plugin)
                    {
                        $component = $this->getOrCreateComponent($plugin['slug']);
                        $company->addComponent(
                            $component->id,
                            $plugin['version'],
                            $plugin['active']
                        );
                    }
                }
                else
                {
                    $component = $this->getOrCreateComponent($value['slug'], $type);
                    $company->addComponent(
                        $component->id,
                        $value['version'],
                        TRUE,
                        $type
                    );
                }
            }

            return response()->json(
                [
                    'success' => TRUE,
                    'message' => 'Components successfully created',
                ], IlluminateResponse::HTTP_OK);
        }
        catch (\Throwable $th)
        {
            return response()->json(
                [
                    'success' => FALSE,
                    'error' => [
                        'code' => 100,
                        'messages' => $th->getMessage()
                    ]
                ], IlluminateResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function feedback(Request $request)
    {
        $url = $request->url;
        $status = $request->status;
        $feedback = Company::where('url', $url)
                            ->first()
                            ->feedback()
                            ->where('status', $status)
                            ->get();

        return response()->json(
            [
                'success' => TRUE,
                'data' => $feedback,
            ], IlluminateResponse::HTTP_OK);
    }

    public function report(Request $request)
    {
        $url = $request->url;
        $insights = Company::whereUrl($url)->first()->insights()->get(['general', 'created_at']);

        return response()->json(
            [
                'success' => TRUE,
                'data' => $insights,
            ], IlluminateResponse::HTTP_OK);
    }

    private function getOrCreateComponent($slug, $type = 'plugin')
    {
        $component = Component::where('slug', $slug)
                                ->where('component_type', $type)
                                ->first();

        if (is_null($component))
        {
            $path = $type == static::WORDPRESS ? 'wordpresses' : $type .'s';

            $vulnData = $this->vulnReport($path, $slug);
            if (isset($vulnData['error']))
            {
               throw new Exception($vulnData['error']);
            }
            $version = $type == static::WORDPRESS ? key((array)$vulnData) : null;

            $component = Component::create($slug, $vulnData, $type, $version);

            $newSlug = $type == static::WORDPRESS ? $version : $slug;
            $vulnerabilities = $vulnData[$newSlug]['vulnerabilities'];

            foreach ($vulnerabilities as &$vuln)
            {
                $vulnerability = Vulnerability::create($vuln);
                $component->addVulnerability($vulnerability->id);
            }
        }

        return $component;
    }

    private function vulnReport($path, $slug)
    {
        $url = getenv('WP_VULN_BASE_URL'). '/' .$path. '/' .$slug;
        $response = Http::withToken(getenv('WP_VULN_TOKEN'))->get($url);
        return $response->json();
    }
}
