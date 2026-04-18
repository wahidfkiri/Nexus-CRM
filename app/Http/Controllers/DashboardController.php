<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        // Vérifier que l'utilisateur est connecté
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        $user = Auth::user();
        
        // Debug - vérifier que l'utilisateur existe
        if (!$user) {
            return redirect()->route('login')->with('error', 'Session expirée');
        }
        
        return view('dashboard', compact('user'));
    }
}