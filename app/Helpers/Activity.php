<?php
namespace App\Helpers;

use App\Models\ActivityLog;

class Activity
{
    public static function add($action, $model = null, $model_id = null, $details = null)
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $model_id,
            'details' => $details,
            'ip' => request()->ip(),
        ]);
    }
}
