<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';
    protected $guarded = [];

    public function apps()
    {
        return $this->hasMany(App::class, 'project_id');
    }
}
