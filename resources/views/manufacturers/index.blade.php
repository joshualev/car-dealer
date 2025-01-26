<x-layout>
    <div class="space-y-10">
        <section class="text-center pt-6">
            <h2 class="font-bold text-3xl">Let's Find Your Next Car</h2>
            <div class="flex flex-col gap-4 mt-4 max-w-96 mx-auto">
                <p>Ready to find your next car?</p>
               <a href="/search"><x-forms.button>Search now</x-forms.button></a>
            </div>
        </section>

        <section class="pt-4">

            <div class="grid lg:grid-cols-3 gap-8 mt-6">
                @foreach($manufacturers as $manufacturer)
                    <x-manufacturer-card :$manufacturer />
                @endforeach
            </div>
        </section>

    </div>
</x-layout>
