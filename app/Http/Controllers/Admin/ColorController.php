<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\products\Color;

class ColorController extends Controller
{
    //// ColorsController.php
public function index()
{
    $colors = Color::all();
    return response()->json([
        'colors' => $colors,
        'message' => $colors->isEmpty() ? 'No colors found' : 'Colors retrieved successfully',
    ]);
}

}
