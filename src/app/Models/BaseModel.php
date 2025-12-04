<?php

namespace CiInbox\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base Model
 * 
 * All application models extend this base class.
 * Provides common functionality and conventions.
 */
abstract class BaseModel extends Model
{
    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be cast.
     * 
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     * Override this in child models.
     * 
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for serialization.
     * Override this in child models.
     * 
     * @var array
     */
    protected $hidden = [];
}
