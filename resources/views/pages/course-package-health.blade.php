<x-filament-panels::page>
    <div class="space-y-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Configuration</h2>

            <dl class="mt-4 divide-y divide-gray-100 text-sm dark:divide-gray-800">
                @foreach ($this->getConfigurationStatus() as $label => $value)
                    <div class="grid grid-cols-1 gap-1 py-3 md:grid-cols-3">
                        <dt class="font-medium text-gray-700 dark:text-gray-300">{{ str($label)->replace('_', ' ')->title() }}</dt>
                        <dd class="break-all text-gray-600 dark:text-gray-400 md:col-span-2">{{ $value ?: 'Not configured' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</x-filament-panels::page>
