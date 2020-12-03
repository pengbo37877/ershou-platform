<?php

namespace App\Http\Controllers\Pc;

use App\Http\Controllers\Controller;

class PcController extends Controller
{
    public function index() {
        $url = env('APP_URL');

        return view('pc.index', compact('url'));
    }

    public function ui() {

        $url = env('APP_URL');
        $user_id = 1;
        $tags = '';
        $coupon = '';

        return view('layouts.app', compact('url', 'user_id', 'tags', 'coupon'));
    }
}