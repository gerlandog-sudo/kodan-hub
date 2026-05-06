<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $table = 'translations';
    protected $guarded = [];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id');
    }
}
