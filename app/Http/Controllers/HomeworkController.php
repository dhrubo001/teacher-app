<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function selectClassForHomework()
    {
        return view('pages.select-class-for-homework');
    }

    public function studentListUnderClass($class_id)
    {
        return view('pages.student-list-under-class', compact('class_id'));
    }

    public function addHomework($period_id)
    {
        return view('pages.add-homework', compact('period_id'));
    }
}
