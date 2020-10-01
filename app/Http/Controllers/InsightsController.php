<?php

namespace App\Http\Controllers;

use App\Audit;
use App\Company;
use App\Insight;
use App\Feedback;
use App\IndustryAverage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response as IlluminateResponse;

class InsightsController extends Controller
{
    const AUDITS = [
        'largest-contentful-paint',
        'speed-index',
        'interactive',
        'total-blocking-time',
        'first-meaningful-paint',
        'cumulative-layout-shift',
        'uses-responsive-images',
        'unminified-javascript',
        'unminified-css',
        'server-response-time',
        'redirects',
        'meta-description',
        'link-text',
        'crawlable-anchors',
        'is-crawlable',
        'robots-txt',
        'structured-data'
    ];

    const FEEDBACK_AUDITS = [
        'is-on-https',
        'uses-optimized-images',
        'unminified-css',
        'unminified-javascript',
        'redirects',
        'server-response-time'
    ];

    const INDUSTRY_AVERAGES = [
        'apparel' => 62,
        'banking_financial' => 66,
        'electronics' => 74,
        'food_groceries' => 74,
        'goverment' => 65,
        'others' => 69
    ];

    public function insights(Request $request)
    {
        if ($request->url)
        {
            $companies = Company::where('url', $request->url)->get();
        }
        else
        {
            $companies = Company::all();
        }

        foreach ($companies as &$company)
        {
            $components = $company->componentsWithVulnerabilities();
            $insightReport = $this->pageSpeedReport($company->url);

            $reportAudits = $insightReport[ 'audits' ];
            $reportCategories = $insightReport[ 'categories' ];

            $insecureComponents = [];
            foreach ($components as &$component)
            {
                $hasVulnerability = false;
                $vulnerabilityIds = [];

                $name = $component->name;
                $type = $component->component_type;
                $version = $component->pivot->version;
                $active = boolval( $component->pivot->active );

                foreach ( $component->vulnerabilities as &$vulnerability )
                {
                    $fixedVersion = version_compare( $version, $vulnerability->fixed_in ) < 0;
                    if ( is_null( $vulnerability->fixed_in ) || $fixedVersion )
                    {
                        $hasVulnerability = true;
                        array_push( $vulnerabilityIds, $vulnerability->id );
                    }
                }

                $feedback = $company->findFeedback($name, $type);

                if ($hasVulnerability)
                {
                    if ($active)
                    {
                        array_push($insecureComponents, $type);
                    }

                    if (is_null($feedback))
                    {
                        $feedback = new Feedback;
                        $feedback->name = $name;
                        $feedback->type = $type;
                        $feedback->impact = $type == 'plugin' ? 'high' : 'medium';
                    }

                    if ($feedback->status != 'archive')
                    {
                        $feedback->status = $active ? 'pending' : 'completed';
                    }

                    $feedback->version = $version;

                    $company->feedback()->save($feedback);
                    $feedback->vulnerabilities()->detach();

                    foreach ($vulnerabilityIds as $vulnerabilityId)
                    {
                        $feedback->vulnerabilities()
                                ->attach($vulnerabilityId);
                    }
                } else {
                    if (!is_null($feedback))
                    {
                        $feedback->status = 'completed';
                        $feedback->version = $version;
                        $company->feedback()->save($feedback);
                    }
                }
            }

            $seo = $reportCategories['seo']['score'] * 100;
            $performance = $reportCategories['performance']['score'] * 100;
            $accessibility = $reportCategories['accessibility']['score'] * 100;
            $https = boolval($reportAudits['is-on-https']['score']);

            if (!$https)
            {
                array_push($insecureComponents, 'performance');
            }

            $security = 100 - (count(array_unique($insecureComponents)) *  25);
            $general = intval(($seo + $security + $performance + $accessibility)/4);

            $insight = new Insight;
            $insight->seo = $seo;
            $insight->general = $general;
            $insight->security = $security;
            $insight->performance = $performance;
            $insight->accessibility = $accessibility;

            $company->insights()->save($insight);

            foreach ($reportAudits as &$audit)
            {
                if (in_array($audit['id'], static::FEEDBACK_AUDITS))
                {
                    if (!boolval($audit['score']))
                    {
                        $feedback = $company->findFeedback($audit['title'], 'performance');

                        if (is_null($feedback))
                        {
                            $feedback = new Feedback;
                            $feedback->name = $audit['title'];
                            $feedback->type = 'performance';
                            $feedback->impact = $audit['id'] == 'is-on-https' ? 'high' : 'medium';
                        }

                        $feedback->status = $audit['score'] == 1 ? 'completed' : 'pending';
                        $company->feedback()->save($feedback);
                    }
                }

                if (in_array($audit['id'], static::AUDITS))
                {
                    $newAudit = new Audit;
                    $newAudit->slug = $audit['id'];
                    $newAudit->title = $audit['title'];
                    $newAudit->score = isset($audit['score']) ? $audit['score'] : null;
                    $newAudit->display_mode = isset($audit['scoreDisplayMode']) ? $audit['scoreDisplayMode'] : null;
                    $newAudit->display_value = isset($audit['displayValue']) ? $audit['displayValue'] : null;
                    $newAudit->numeric_value = isset($audit['numericValue']) ? $audit['numericValue'] : null;

                    $insight->audits()->save($newAudit);
                }
            }

            return response()->json(
                [
                    'success' => TRUE,
                    'general' => $general,
                    'performance' => $performance,
                    'seo' => $seo,
                    'accessibility' => $accessibility,
                    'security' => $security,
                ], IlluminateResponse::HTTP_OK);
        }
    }

    private function pageSpeedReport($url)
    {
        $pageSpeedUrl = getenv('PAGE_SPEED_BASE_URL');
        $pageSpeedToken = getenv('PAGE_SPEED_TOKEN');

        $uri = $pageSpeedUrl. 'pagespeedonline/v5/runPagespeed?url=' .$url. '&key='. $pageSpeedToken .
            '&category=accessibility&category=best-practices&category=performance&category=pwa&category=seo';

        $response = Http::get($uri);

        return $response->json()['lighthouseResult'];
    }
}
