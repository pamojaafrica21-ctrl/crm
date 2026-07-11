<x-crm-layout>
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Profile') }}</h1>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-crm-layout>
