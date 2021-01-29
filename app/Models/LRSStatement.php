<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LRSStatement extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lrs_statements_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'statement_id',
        'statement_uuid',
        'actor_id',
        'class_id',
        'project_id',
        'playlist_id',
        'page_completed',
        'assignment_submitted',
        'object_id',
        'duration',
        'datetime',
        'options',
        'answer',
        'object_name',
        'activity_category',
        'actor_homepage',
        'verb',
        'platform',
        'project_name',
        'score',
        'playlist_name',
        'assignment_id',
        'assignment_name',
        'page',
        'question'
    ];
}
