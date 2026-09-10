<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\User;

class VisitReportController extends Controller
{
    public function index()
    {
        $users = User::all();
        return Inertia::render('Visit_Reports/main_visitreport', [
            'users' => $users
        ]);
    }
}
