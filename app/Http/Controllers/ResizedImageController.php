<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResizedImageController extends Controller
{
    public function show(string $path)
    {
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $response = Storage::disk('public')->response($path);
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }
}
