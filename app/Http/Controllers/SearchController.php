<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\Car;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Handle the incoming search request.
     *
     * @param SearchRequest $request
     * @return View
     */
    public function __invoke(SearchRequest $request)
    {
        // Retrieve the validated input data
        $validated = $request->validated();

        // Extract the search query
        $query = $validated['q'] ?? '';
        $page = $request->input('page', 1);

        // Define a unique cache key based on the query and page
        $cacheKey = 'search_' . md5($query) . '_page_' . $page;

        // Attempt to retrieve from cache
        $cars = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($query) {
            return Car::with(['manufacturer:id,name,country'])
                      ->search($query)
                      ->paginate(12)
                      ->withQueryString();
        });

        // Return the view with the paginated results
        return view('cars.search', compact('cars'));
    }
}
