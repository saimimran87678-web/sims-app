<?php
namespace App\Http\Controllers;
class TestErrorController extends Controller {
    public function index() {
        return view('errors.license-locked');
    }
}
