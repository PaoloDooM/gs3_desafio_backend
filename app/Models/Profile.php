<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    public const PROFILES = [ 'admin' => 0, 'user' => 1];

    public function users(){
        return $this->hasMany(User::class);
    }
}
