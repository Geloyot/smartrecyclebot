<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArmAction extends Model
{
    protected $fillable = [
        'waste_object_id',
        'description',
        'status',
        'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function wasteObject()
    {
        return $this->belongsTo(WasteObject::class);
    }
}
