<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Insight extends Model
{
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['created_at', 'updated_at'];

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
     * Get the audtis for the insight.
     */
    public function audits()
    {
        return $this->hasMany(Audit::class);
    }
}


