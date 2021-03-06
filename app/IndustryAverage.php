<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndustryAverage extends Model
{
    public $industries = [
        'apparel' => 62,
        'banking_financial' => 66,
        'electronics' => 74,
        'food_groceries' => 74,
        'goverment' => 65,
        'others' => 69
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['industry'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['id', 'created_at', 'updated_at'];

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

    public function findByIndustry($industry)
    {
        return $this->where('industry', $industry)
                    ->first();
    }
}
