<x-layout>
    <x-page-heading>Your next Car is waiting</x-page-heading>


    <section class="pb-4">
        <x-forms.form action="{{ route('search') }}" method="GET" class="mt-6 max-w-xl">
            <x-forms.input :label="false" name="q" value="{{ request('q') }}" placeholder="Search for your next car" />
        </x-forms.form>
    </section>

    <section class="text-center mt-2">
        <a href="/">
            <x-forms.button>
                <!-- Back Icon SVG -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                See more Manufacturers
            </x-forms.button>
        </a>
    </section>

    @if($cars->count())
        <div class="grid lg:grid-cols-3 gap-8 mt-6">
            @foreach($cars as $car)
                <x-car-card :car="$car" />
            @endforeach
        </div>

        <div class="mt-6">
            {{ $cars->links() }}
        </div>
    @else
        <div class="mt-6 text-center text-gray-600">
            No cars found matching your search criteria.
        </div>
    @endif
</x-layout>
