<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    public $audits = [
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
     * Get the insight that owns the audit.
     */
    public function insight()
    {
        return $this->belongsTo(Insight::class);
    }

    public function create($type, $name, $score, $displayValue, $numericValue, $scoreDisplayMode)
    {
        $audit                = new Audit;
        $audit->slug          = $type;
        $audit->title         = $name;
        $audit->score         = $score;
        $audit->display_mode  = $scoreDisplayMode;
        $audit->display_value = $displayValue;
        $audit->numeric_value = $numericValue;

        return $audit;
    }
}
