<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

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
     * Get the company that owns the feeback.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the vulnerabilities of the component.
     */
    public function vulnerabilities()
    {
        return $this->belongsToMany(
            Vulnerability::class,
            'feedback_vulnerabilities',
            'feedback_id',
            'vulnerability_id');
    }
}
