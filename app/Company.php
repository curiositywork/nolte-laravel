<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['created_at', 'updated_at', 'id'];

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

    public $filleable = [
        'url', 'size', 'industry', 'business_type'
    ];

    /**
     * Get the customer record associated with the company.
     *
     * public function customer()
     * {
     *   return $this->hasOne(Customer::class);
     * }
    */

    public $rule = [
        'url' => [
            'required',
            'min:15',
            'regex:/((http:|https:)\/\/)[^\/]+/'
        ]
    ];

    public $createRule = [
        'url' => [
            'required',
            'unique:companies',
            'min:15',
            'regex:/((http:|https:)\/\/)[^\/]+/'
        ],
        'size' => 'required|in:micro,small,medium,large',
        'industry' => 'required|in:apparel,banking_financial,electronics,food_groceries,goverment,others',
        'business_type' => 'required|in:digital,ecommerce,both',
    ];


    public function findByUrl($url)
    {
        return $this->whereUrl($url)
                    ->first();
    }

    public function feedbackByStatus($url, $status)
    {
        return $this->whereUrl('url', $url)
                    ->first()
                    ->feedback()
                    ->where('status', $status)
                    ->get();
    }

    public function insightsByWeek()
    {
        return $this->insights()
                    ->get(['general'])
                    ->groupBy(function($date)
                    {
                        return Carbon::parse($date->created_at)->format('W');
                    })
                    ->map(function($row) {
                        return round($row->sum('general')/count($row));
                    });
    }

    /**
     * Get the feedback for the company.
     */
    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Get the insights for the company.
     */
    public function insights()
    {
        return $this->hasMany(Insight::class);
    }

    /**
     * Get the components of the company.
     */
    public function components()
    {
        return $this->belongsToMany(
            Component::class,
            'companies_components',
            'company_id',
            'component_id');
    }

    public function componentsWithVulnerabilities()
    {
        return $this->components()
                    ->withPivot('version')
                    ->withPivot('active')
                    ->with('vulnerabilities')
                    ->get();
    }

    public function findFeedback($name, $type)
    {
        return $this->feedback()
                    ->where('name', $name)
                    ->where('type', $type)
                    ->first();
    }

    public function deactivateComponents(): void
    {
        $components = $this->components()
                              ->wherePivot('active', true)
                              ->get();

        foreach ($components as $key => $deletedComponent)
        {
            $this->components()
                 ->updateExistingPivot(
                    $deletedComponent->id,
                    ['active' => false]
                 );
        }
    }

    public function addComponent($componentId, $version, $active, $type = 'plugin'): void
    {        
        $exists = $this->components()->wherePivot('component_id', $componentId)->first();
        if (is_null($exists))
        {
            $this->components()
                ->attach(
                    $componentId,
                    [
                        'version' => $version,
                        'active' => $active,
                        'type' => $type
                    ]
                );
        }
        else 
        {
            $this->components()->updateExistingPivot(
                $exists->id, 
                [
                    'version' => $version,
                    'active' => $active,
                    'type' => $type
                ]
            );
        }     
    }
}
