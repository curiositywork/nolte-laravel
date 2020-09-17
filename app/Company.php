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

    /**
     * Get the customer record associated with the company.
     */
    public function customer()
    {
        return $this->hasOne(Customer::class);
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
