<form method="POST" action="{{ route('doctor.patients.emergency-access', ['patient' => $patient, 'resourceType' => $resourceType, 'resourceId' => $resourceId]) }}" class="rounded-xl border border-amber-200 bg-amber-50 p-4 space-y-3">
    @csrf
    <p class="text-xs font-semibold text-amber-900">{{ ucfirst(str_replace('_', ' ', $resourceType)) }} #{{ $resourceId }} — consent required</p>
    <label for="emergency-reason-{{ $resourceType }}-{{ $resourceId }}" class="block text-xs font-semibold text-gray-700">Reason for emergency access</label>
    <textarea id="emergency-reason-{{ $resourceType }}-{{ $resourceId }}" name="reason" required minlength="10" rows="3" class="block w-full rounded-lg text-sm border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20">{{ old('reason') }}</textarea>
    @error('reason')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
    <button type="submit" class="inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100">
        Request Emergency Access
    </button>
</form>
