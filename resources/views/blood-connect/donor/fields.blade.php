<x-blood-connect.field name="full_name" label="Full name" :value="$donor->full_name" required maxlength="255" />
<x-blood-connect.select name="blood_group" label="Blood group" :options="$groups" :value="$donor->blood_group" placeholder="Select blood group" required />
<x-blood-connect.field name="phone" label="Bangladeshi mobile number" type="tel" :value="$donor->phone" required maxlength="20" />
<x-blood-connect.field name="district" label="District" :value="$donor->district" required maxlength="100" />
<x-blood-connect.field name="area" label="Current location" :value="$donor->area" required maxlength="255" />
<x-blood-connect.field name="last_donation_date" label="Last donation date (optional)" type="date" :value="$donor->last_donation_date?->toDateString()" :max="today()->toDateString()" />
<x-blood-connect.select name="availability_status" label="Availability" :options="['available','temporarily_unavailable','unavailable']" :value="$donor->availability_status ?? 'unavailable'" required />
<div class="flex flex-col gap-4 sm:col-span-2">
    <div><input type="hidden" name="public_profile" value="0"><label class="flex items-start gap-3"><input type="checkbox" name="public_profile" value="1" @checked(old('public_profile', $donor->public_profile)) class="mt-1 rounded text-red-700"><span>Allow my donor profile to be discovered for matching.</span></label></div>
    <div><input type="hidden" name="contact_consent" value="0"><label class="flex items-start gap-3"><input type="checkbox" name="contact_consent" value="1" @checked(old('contact_consent', $donor->contact_consent)) class="mt-1 rounded text-red-700"><span>Allow my contact details to be shared for blood donation coordination.</span></label></div>
    <p class="text-sm text-stone-600">Phone verification is not available in Medixa. Saving a phone number does not mark it verified. Public profiles are used for matching; this module has no public donor phone directory.</p>
</div>

