<div wire:poll.15s >
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- Stats Cards --}}
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            @foreach ($stats as $stat)
                <div class="p-4 bg-green-50 dark:bg-green-800 rounded-xl border border-green-200 dark:border-green-700">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</h4>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stat['count'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Notifications Table --}}
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900">
            <div class="p-4 overflow-auto h-full">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h3>
                    <div>
                        <a href="{{ route('notifications_export.pdf') }}" class="mx-2 mt-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Export Notifications to PDF
                        </a>
                        <a href="{{ route('notifications_export.csv') }}" class="mx-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Export Notifications to CSV
                        </a>
                        <button wire:click="markAllRead" class="px-3 text-sm text-blue-600 hover:underline dark:text-blue-400 cursor-pointer">
                            Mark All Notifications as Read
                        </button>
                    </div>

                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-green-50 dark:bg-green-900 divide-y divide-gray-200 dark:divide-neutral-700">
                        @forelse ($this->notifications as $notif)
                            <tr @class(['bg-green-100 dark:bg-green-800' => !$notif->is_read])>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $notif->title }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $notif->message }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <span @class([
                                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                        'bg-blue-100 text-blue-800'   => $notif->level === 'info',
                                        'bg-yellow-100 text-yellow-800' => $notif->level === 'warning',
                                        'bg-red-100 text-red-800'     => $notif->level === 'error',
                                    ])>
                                        {{ ucfirst($notif->level) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $notif->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-2 text-right">
                                    @if (! $notif->is_read)
                                        <button wire:click="markRead({{ $notif->id }})" class="text-xs text-blue-600 hover:underline cursor-pointer">
                                            Mark as Read
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    No notifications found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
