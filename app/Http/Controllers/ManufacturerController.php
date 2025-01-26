<?php

namespace App\Http\Controllers;

use App\Models\Manufacturer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManufacturerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $manufacturers = Manufacturer::all();

        return view('manufacturers.index', compact('manufacturers'), [
            'manufacturers' => $manufacturers
        ]);
    }

    /**
     * Display the specified manufacturer.
     *
     * @param Manufacturer $manufacturer
     * @return View
     */
    public function show(Manufacturer $manufacturer)
    {
        // Eager load cars to prevent N+1 problem
        $manufacturer->load('cars');

        return view('manufacturers.show', compact('manufacturer'));
    }
}
