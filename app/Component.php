<?php

namespace App;

use App\Vulnerability;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    const WORDPRESS = 'wordpress';
    const THEME = 'theme';
    const PLUGIN = 'plugin';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['created_at', 'updated_at', 'company_id'];

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the companies that owns the components.
     */
    public function companies()
    {
        return $this->belongsToMany(
            Company::class,
            'companies_components',
            'component_id',
            'company_id');
    }

    /**
     * Get the vulnerabilities of the component.
     */
    public function vulnerabilities()
    {
        return $this->belongsToMany(
            Vulnerability::class,
            'components_vulnerabilities',
            'component_id',
            'vulnerability_id');
    }

    public function addVulnerability($vulnerabilityId)
    {
        return $this->vulnerabilities()->attach($vulnerabilityId);
    }

    public static function create($slug, $data, $type, $version)
    {
        $closed = false;
        $popular = true;

        if ($type == static::WORDPRESS) {
            $name = ucfirst(static::WORDPRESS) .' '. $version;
            $friendly_name = $name;
            $latest_version = $version;
        } else {
            $name = ucfirst($slug);
            $popular = $data[$slug]['popular'];
            $friendly_name =  $data[$slug]['friendly_name'];
            $latest_version = $data[$slug]['latest_version'];

            if (isset($data[$slug]['closed']) && isset($data[$slug]['closed_reason'])) {
                $closed = $data[$slug]['closed'];
                $closed_reason = $data[$slug]['closed_reason'];
            }
        }

        $component = new Component;
        $component->name = $name;
        $component->slug = $slug;
        $component->closed = $closed;
        $component->popular = $popular;
        $component->latest_version = $latest_version;
        $component->component_type = $type;
        $component->friendly_name = $friendly_name;
        if ($closed) {
            $component->closed_reason = $closed_reason;
        }
        $component->save();

        return $component;
    }
}
