@if(session('success'))<p role="status" class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900">{{ session('success') }}</p>@endif
@if($errors->any())
<div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-900">
    <p class="font-semibold">Please check the following:</p>
    <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

