<?php

namespace App\Http\Services;

use App\Audit;
use App\Insight;
use App\Feedback;
use App\IndustryAverage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class InsightsService
{
    private $audit;
    private $insight;
    private $feedback;

    public function __construct()
    {
        $this->audit    = new Audit;
        $this->insight  = new Insight;
        $this->feedback = new Feedback;
    }

    public function generateFeedback($companies)
    {
        $general = 0;

        foreach ($companies as &$company) {
            $components       = $company->componentsWithVulnerabilities();
            $insightReport    = $this->pageSpeedReport($company->url);
            $reportAudits     = $insightReport['audits'];
            $reportCategories = $insightReport['categories'];

            $insecureComponents = $this->insecureComponents($company, $components);

            $https = boolval($reportAudits['is-on-https']['score']);
            if (!$https) {
                array_push($insecureComponents, 'performance');
            }

            $seo           = $reportCategories['seo']['score'] * 100;
            $performance   = $reportCategories['performance']['score'] * 100;
            $accessibility = $reportCategories['accessibility']['score'] * 100;
            $security      = 100 - (count(array_unique($insecureComponents)) *  25);
            $general       = intval(($seo + $security + $performance + $accessibility)/4);
            $insight       = $this->insight->create($seo, $performance, $accessibility, $security, $general);

            $company->insights()->save($insight);

            $this->reportAudits($company, $insight, $reportAudits);
        }

        return $general;
    }

    private function reportAudits($company, $insight, $audits)
    {
        foreach ($audits as &$audit) {
            $type             = $audit['id'];
            $name             = $audit['title'];
            $score            = $this->isSet($audit['score']);
            $displayValue     = $this->isSet($audit['displayValue']);
            $numericValue     = $this->isSet($audit['numericValue']);
            $scoreDisplayMode = $this->isSet($audit['scoreDisplayMode']);

            if (in_array($type, $this->feedback->audits)) {
                if (($displayValue == 'binary' && !boolval($score)) || $score <= 0.5) {
                    $feedback = $company->findFeedback($name, $type);

                    if (is_null($feedback)) {
                        $impact   = $this->setImpact($type);
                        $feedback = $this->feedback->create($name, $type, $impact);
                    }

                    $feedback->status = $score > 0.5 ? 'completed' : 'pending';
                    $company->feedback()->save($feedback);
                }
            }

            if (in_array($type, $this->audit->audits)) {
                $newAudit = $this->audit->create($type, $name, $score, $displayValue, $numericValue, $scoreDisplayMode);
                $insight->audits()->save($newAudit);
            }
        }
    }

    private function insecureComponents($company, $components)
    {
        $insecureComponents = [];
        foreach ($components as &$component) {
            $hasVulnerability = false;
            $vulnerabilityIds = [];

            $name    = $component->name;
            $type    = $component->component_type;
            $version = $component->pivot->version;
            $active  = boolval($component->pivot->active);

            foreach ($component->vulnerabilities as &$vulnerability) {
                $fixedVersion = version_compare($version, $vulnerability->fixed_in) < 0;
                if (is_null($vulnerability->fixed_in) || $fixedVersion) {
                    $hasVulnerability = true;
                    array_push($vulnerabilityIds, $vulnerability->id);
                }
            }

            $feedback = $company->findFeedback($name, $type);

            if ($hasVulnerability) {
                if ($active) {
                    array_push($insecureComponents, $type);
                }

                if (is_null($feedback)) {
                    $impact   = $this->setImpact($type);
                    $feedback = $this->feedback->create($name, $type, $impact);
                }

                if ($feedback->status != 'archived') {
                    $feedback->status = $active ? 'pending' : 'completed';
                }

                $feedback->version = $version;

                $company->feedback()->save($feedback);
                $this->attachVulnerabilities($feedback, $vulnerabilityIds);
            } else {
                if (!is_null($feedback)) {
                    $feedback->status  = 'completed';
                    $feedback->version = $version;
                    $company->feedback()->save($feedback);
                }
            }
        }

        return $insecureComponents;
    }

    private function attachVulnerabilities($feedback, $ids)
    {
        $feedback->vulnerabilities()->detach();
        foreach ($ids as $id) {
            $feedback->vulnerabilities()->attach($id);
        }
    }

    private function setImpact($value)
    {
        return $value == 'plugin' || 'is-on-https' ? 'high' : 'medium';
    }
    private function isSet($value)
    {
        return isset($value) ? $value : null;
    }  
    
    private function pageSpeedReport($url)
    {
        $pageSpeedUrl   = getenv('PAGE_SPEED_BASE_URL');
        $pageSpeedToken = getenv('PAGE_SPEED_TOKEN');

        $uri = $pageSpeedUrl. 'pagespeedonline/v5/runPagespeed?url=' .$url. '&key='. $pageSpeedToken .
            '&category=accessibility&category=best-practices&category=performance&category=pwa&category=seo';

        $response = Http::get($uri);

        if ($response->ok()) {
            return $response->json()['lighthouseResult'];
        }
        Log::warning('page_speed_error '. $response->json());
        return null;
    }
}
