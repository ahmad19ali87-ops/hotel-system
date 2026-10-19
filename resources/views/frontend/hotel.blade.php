@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-10">
	<a href="{{ route('home') }}" class="text-blue-700 hover:underline">العودة إلى الفنادق</a>

	<section class="mt-6 overflow-hidden rounded-lg bg-white shadow">
		@if($hotel->logo)
			<img src="{{ asset($hotel->logo) }}" alt="{{ $hotel->name }}" class="h-72 w-full object-cover">
		@endif

		<div class="p-6 md:p-8">
			<div class="flex flex-wrap items-start justify-between gap-4">
				<div>
					<h1 class="text-3xl font-bold text-gray-900">{{ $hotel->name }}</h1>
					@if($hotel->address)
						<p class="mt-2 text-gray-600">{{ $hotel->address }}</p>
					@endif
				</div>
				@if($hotel->stars)
					<p class="text-lg font-semibold text-amber-600">{{ $hotel->stars }} نجوم</p>
				@endif
			</div>

			@if($hotel->description)
				<p class="mt-6 leading-8 text-gray-700">{{ $hotel->description }}</p>
			@endif

			<div class="mt-8 flex flex-wrap items-center gap-4">
				<a href="{{ url('hotel/' . $hotel->slug . '/rooms') }}" class="rounded bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">
					استعرض الغرف
				</a>
				@if($hotel->phone)
					<a href="tel:{{ $hotel->phone }}" class="font-semibold text-blue-700 hover:underline">
						اتصل بنا: {{ $hotel->phone }}
					</a>
				@endif
			</div>
		</div>
	</section>
</div>
@endsection