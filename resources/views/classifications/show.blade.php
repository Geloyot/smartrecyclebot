<x-layouts.app.sidebar title="Waste Object Details">
    <flux:main>
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-4">Waste Object Details</h1>

            <div class="bg-white shadow rounded-lg p-4">
                <p><strong>ID:</strong> {{ $wasteObject->id }}</p>
                <p><strong>Type:</strong> {{ $wasteObject->classification }}</p>
                <p><strong>Detected At:</strong> {{ $wasteObject->created_at->format('Y-m-d H:i:s') }}</p>
                <p><strong>Description:</strong> {{ $wasteObject->description ?? 'N/A' }}</p>
            </div>

            <a href="{{ route('classifications') }}"
               class="inline-block mt-4 text-blue-500 hover:text-blue-700">
               ← Back to Classifications
            </a>
        </div>
    </flux:main>
</x-layouts.app.sidebar>
