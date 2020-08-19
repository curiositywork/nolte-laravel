<?php

namespace App\Http\Controllers;

use App\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response as IlluminateResponse;

class PageSpeedController extends Controller
{
    public function insights(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'url' => [
                    'required',
                    'min:15',
                    'regex:/((http:|https:)\/\/)[^\/]+/',
                ],
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

        $pageSpeedUrl = env('PAGE_SPEED_BASE_URL');
        $pageSpeedKey = env('PAGE_SPEED_TOKEN');

        $url = $request->get('url');
        $company = Company::where('url', $url)->first();

        $uri = $pageSpeedUrl. 'pagespeedonline/v5/runPagespeed?url=' .$company->url. '&key='. $pageSpeedKey .
            '&category=accessibility&category=best-practices&category=performance&category=pwa&category=seo';

        $response = Http::get($uri);

        return response()->json(['success' => TRUE, 'data' => $response->json()], IlluminateResponse::HTTP_OK);
    }
}
