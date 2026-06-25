@extends('layouts.app')

@section('title', 'New Travel Request')
@section('eyebrow', 'Travel')
@section('page_title', 'Create Travel Request')

@section('content')
    <div class="max-w-3xl">
        <div class="flex items-center gap-2 mb-6">
            <a href="{{ route('travel-requests.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-900">Travel Request Details</h2>
                <p class="text-sm text-slate-500 mt-0.5">Complete the form below. Fields marked with * are required.</p>
            </div>

            <form method="POST" action="{{ route('travel-requests.store') }}" enctype="multipart/form-data" class="p-5 sm:p-6 space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Department *</label>
                    <select name="department_id" required
                            class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Travel Category *</label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['academic' => ['Academic', 'Conferences, trainings, seminars for instruction/faculty development', 'graduation-cap'], 'research' => ['Research', 'Research activities, publications, and extension programs', 'microscope']] as $val => [$label, $desc, $icon])
                        <label class="relative flex items-start gap-3 p-3 border rounded-xl cursor-pointer has-[:checked]:border-ua-red-400 has-[:checked]:bg-ua-red-50 border-slate-200 hover:bg-slate-50 transition-colors">
                            <input type="radio" name="category" value="{{ $val }}" {{ old('category') === $val ? 'checked' : '' }} class="mt-0.5 accent-ua-red-600" required>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $label }}</p>
                                <p class="text-xs text-slate-500">{{ $desc }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('category')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Destination *</label>
                    <div class="relative">
                        <i data-lucide="map-pin" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="destination" value="{{ old('destination') }}" required
                               placeholder="e.g. Manila, Philippines"
                               class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Purpose *</label>
                    <textarea name="purpose" rows="4" required
                              placeholder="Briefly describe the purpose of this trip..."
                              class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">{{ old('purpose') }}</textarea>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date From *</label>
                        <div class="relative">
                            <i data-lucide="calendar" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="date" name="date_from" value="{{ old('date_from') }}" required
                                   class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date To *</label>
                        <div class="relative">
                            <i data-lucide="calendar" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="date" name="date_to" value="{{ old('date_to') }}" required
                                   class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Estimated Cost (₱) *</label>
                    <div class="relative">
                        <span class="text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 text-sm">₱</span>
                        <input type="number" step="0.01" name="estimated_cost" value="{{ old('estimated_cost') }}" required
                               placeholder="0.00"
                               class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-ua-red-200 focus:border-ua-red-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                        Supporting Documents
                        <span class="font-normal text-slate-400">(optional — up to 5 files, 5 MB each)</span>
                    </label>
                    <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer hover:border-ua-red-300 hover:bg-ua-red-50/30 transition-colors">
                        <i data-lucide="upload-cloud" class="w-6 h-6 text-slate-400 mb-1"></i>
                        <span class="text-xs text-slate-500">Click or drag files here</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">PDF, Word, JPG, PNG</span>
                        <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="sr-only">
                    </label>
                    @error('attachments.*')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <a href="{{ route('travel-requests.index') }}"
                       class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-ua-red-600 hover:bg-ua-red-700 text-white rounded-xl text-sm font-semibold shadow-sm">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
