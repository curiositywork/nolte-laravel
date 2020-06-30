<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PageSpeedController extends Controller
{
    public function insights(Request $request)
    {
        $url = $request->input('url');
        $baseUrl = env('PAGE_SPEED_BASE_URL');
        $key = env('PAGE_SPEED_TOKEN');
        $uri = $baseUrl. 'pagespeedonline/v5/runPagespeed?url=' .$url. '&key='. $key;
        $response = Http::get($uri);
        return response()->json($response->body(), 200);
        
    }
}
