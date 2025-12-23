<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FrontendController extends Controller
{
    public function index()
    {
        $indexHtmlPath = base_path('frontend/dist/index.html');
        
        // Проверяем наличие собранного React приложения
        if (File::exists($indexHtmlPath)) {
            return response()->file($indexHtmlPath);
        }
        
        // Если frontend отсутствует, показываем заглушку
        return view('frontend.placeholder');
    }
    
}

