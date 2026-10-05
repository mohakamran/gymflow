<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'phone', 'interest', 'message', 'status'])]
class Enquiry extends Model
{
    use BelongsToTenant;

    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'converted' => 'Converted', 'closed' => 'Closed'];
}
