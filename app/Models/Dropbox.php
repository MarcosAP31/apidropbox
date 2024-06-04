<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dropbox extends Model
{
    //use HasFactory;
    protected $table = 'access_token_dropbox';
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $fillable = ['token'];
}
