<?php

namespace App\Http\Controllers;

use App\Models\Silde;
use Illuminate\Http\Request;

class HomeController extends Controller
{
   public function index()
{
    // 1. Đặt tên biến là $slides (có s)
    $slides = Silde::where('status', 1)->get()->take(3);
    
    // 2. Truyền sang view cũng là slides (có s)
    return view('index', compact('slides'));
}
}
