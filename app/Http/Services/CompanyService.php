<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Audit;
use App\Company;
use App\Insight;
use App\Customer;
use App\Feedback;
use App\Component;
use App\Vulnerability;
use App\IndustryAverage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response as IlluminateResponse;

class CompanyController extends Controller
{
  private $component;
  private $vulnerability;

  public function __construct()
  {
      $this->component = new Component;
      $this->vulnerability = new Vulnerability;
  }

  public function getOrCreateComponent($slug, $type)
    {
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
                $vulnerability = $this->vulnerabilities->create($vuln);
                $component->addVulnerability($vulnerability->id);
            }
        }

        return $component;
    }

}