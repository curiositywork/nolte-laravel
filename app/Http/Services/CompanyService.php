<?php

namespace App\Http\Services;

use Exception;
use App\Component;
use App\Vulnerability;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CompanyService
{
    const WORDPRESS = 'wordpress';

    private $component;
    private $vulnerability;

    public function __construct()
    {
        $this->component = new Component;
        $this->vulnerability = new Vulnerability;
    }

    public function getOrCreateComponent($slug, $type)
    {
        try {
            $component = $this->component->findByType($slug, $type);
            if (is_null($component)) {
                $path = $type == static::WORDPRESS ? 'wordpresses' : $type .'s';

                $vulnData = $this->vulnReport($path, $slug);
                if (is_null($vulnData)) {
                    return null;
                }
                $version = $type == static::WORDPRESS ? key((array)$vulnData) : null;

                $component = $this->component->create($slug, $vulnData, $type, $version);

                $newSlug = $type == static::WORDPRESS ? $version : $slug;
                $vulnerabilities = $vulnData[$newSlug]['vulnerabilities'];

                foreach ($vulnerabilities as &$vuln) {
                    $vulnerability = $this->vulnerability->create($vuln);
                    $component->addVulnerability($vulnerability->id);
                }
            }

            return $component;
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }

    private function vulnReport($path, $slug)
    {
        $url = getenv('WP_VULN_BASE_URL'). '/' .$path. '/' .$slug;
        $response = Http::withHeaders(['Authorization' => 'Token token=' . getenv('WP_VULN_TOKEN')])->get($url);
        if ($response->ok()) {
            return $response->json();
        }
        Log::warning('vuln_report_error '. $response->body());
        return null;
    }
}