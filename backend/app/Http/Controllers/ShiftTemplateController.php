<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShiftTemplateResource;
use App\Models\ShiftTemplate;

class ShiftTemplateController extends Controller
{
    public function index()
    {
        return ShiftTemplateResource::collection(ShiftTemplate::all());
    }
}
