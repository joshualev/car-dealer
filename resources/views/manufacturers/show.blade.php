<x-layout>
    <div class="space-y-10">
        <section class="text-center">

            <h2 class="font-bold text-3xl mt-8">{{ $manufacturer->name }}</h2>
            <div class="flex flex-col gap-4 mt-4 max-w-96 mx-auto">
                <p>{{$manufacturer->description}}</p>
                <p class="text-indigo-500 font-semibold">{{ $manufacturer->country }}</p>
            </div>
            <hr class="mt-6"/>
        </section>

        <section>
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

        <section class="pt-4">
        @if($manufacturer->cars->count())
        <div class="grid lg:grid-cols-3 gap-8">
            @foreach($manufacturer->cars as $car)
                    <x-car-card :$car />
                @endforeach
        </div>
        @else
            <p>No cars found for this manufacturer.</p>
        @endif
        </section>
    </div>
</x-layout>
