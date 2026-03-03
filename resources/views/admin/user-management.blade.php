@php
    use Illuminate\Support\Carbon;
    use App\Models\User;
    use App\Models\Role;

    // simple counters
    $totalUsers = User::count();
    $adminCount = User::where('role_id', 1)->count();
    $userCount = User::where('role_id', 2)->count();
    $lastUser = User::latest('created_at')->first();

    // Read query parameters (or defaults)
    $sort = request()->query('sort', 'id');
    $direction = request()->query('direction', 'asc');

    // Validate sort field
    $allowedSorts = ['id','name','email','role','created_at','updated_at','status','last_seen','last_status_updated'];
    if (!in_array($sort, $allowedSorts)) $sort = 'id';

    // Base query
    $usersQuery = User::with('role')->withMax('sessions', 'last_activity');

    // Handle special sort columns
    if ($sort === 'role') {
        $usersQuery = $usersQuery
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->orderBy('roles.name', $direction)
            ->select('users.*');
    } elseif ($sort === 'last_seen') {
        $usersQuery = $usersQuery->orderBy('sessions_max_last_activity', $direction);
    } elseif ($sort === 'last_status_updated') {
        $usersQuery = $usersQuery->orderBy('last_status_updated', $direction);
    } else {
        $usersQuery = $usersQuery->orderBy($sort, $direction);
    }

    // Paginate
    $users = $usersQuery->paginate(25)->withQueryString();

    // Roles for add/edit dropdown
    $roles = Role::get();
@endphp


<x-layouts.app :title="__('User Management')">
    <h1 class="text-2xl font-bold mb-6">User Management</h1>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-2">
            {{-- CARD-SIZED SECTION TO DISPLAY USER STATISTICS --}}
            <div
                class="relative rounded-xl border border-green-200 dark:border-green-700 p-4 bg-green-50 dark:bg-green-900">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">User Summary</h2>

                <div class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <p>👥 <strong class="font-medium">{{ $totalUsers }}</strong> total users</p>
                    <div class="pl-4 space-y-1 text-sm">
                        <p>🔧 Admins: <strong>{{ $adminCount }}</strong></p>
                        <p>🙍 Regular Users: <strong>{{ $userCount }}</strong></p>
                    </div>
                </div>

                <div class="mt-4 border-t border-neutral-300 dark:border-neutral-600 pt-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Latest User</h3>
                    @if ($lastUser)
                        <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                            {{ $lastUser->name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Joined {{ $lastUser->created_at->diffForHumans() }}
                        </p>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No users yet.</p>
                    @endif
                </div>
            </div>
        </div>
        <div
            class="relative h-full flex-1 rounded-xl border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900 p-4">
            <div class="overflow-x-auto h-full">
                <div class="mb-2 flex items-center">
                    <!-- Add User button on the left -->
                    <button
                        id="openUserModal"
                        type="button"
                        class="cursor-pointer bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring focus:ring-green-300"
                    >
                        Add User
                    </button>
                    <a href="{{ route('users_export.pdf') }}" class="mx-3 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Export PDF
                    </a>
                    <a href="{{ route('users_export.csv') }}" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Export CSV
                    </a>
                    <div class="flex-1"></div>
                    <form method="GET" action="{{ route('user_management') }}" class="flex items-center space-x-2">
                        <label for="sort" class="text-sm font-medium">Sort by:</label>
                        <select name="sort" id="sort" onchange="this.form.submit()"
                            class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring focus:border-blue-300">
                            <option value="id" {{ $sort == 'id' ? 'selected' : '' }}>ID</option>
                            <option value="name" {{ $sort == 'name' ? 'selected' : '' }}>Full Name</option>
                            <option value="email" {{ $sort == 'email' ? 'selected' : '' }}>Email</option>
                            <option value="role" {{ $sort == 'role' ? 'selected' : '' }}>Role</option>
                            <option value="created_at" {{ $sort == 'created_at' ? 'selected' : '' }}>Created</option>
                            <option value="updated_at" {{ $sort == 'updated_at' ? 'selected' : '' }}>Updated</option>
                            <option value="status" {{ $sort == 'status' ? 'selected' : '' }}>Status</option>
                            <option value="last_seen" {{ $sort == 'last_seen' ? 'selected' : '' }}>Last Seen</option>
                            <option value="last_status_updated" {{ $sort == 'last_status_updated' ? 'selected' : '' }}>Status Updated At</option>
                        </select>

                        <label for="direction" class="text-sm font-medium">Direction:</label>
                        <select name="direction" id="direction" onchange="this.form.submit()"
                            class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring focus:border-blue-300">
                            <option value="asc" {{ $direction == 'asc' ? 'selected' : '' }}>Ascending</option>
                            <option value="desc" {{ $direction == 'desc' ? 'selected' : '' }}>Descending</option>
                        </select>
                    </form>
                </div>
                <table class="min-w-full divide-y divide-green-200 dark:divide-green-700 text-sm">
                    <thead class="bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200 border-green-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold w-1/8">ID No.</th>
                            <th class="px-13 py-3 text-left font-semibold w-1/4">Full Name</th>
                            <th class="px-13 py-3 text-left font-semibold w-1/4">Email</th>
                            <th class="px-4 py-3 text-left font-semibold w-1/8">Role</th>
                            <th class="px-11 py-3 text-left font-semibold w-1/4">Created</th>
                            <th class="px-11 py-3 text-left font-semibold w-1/4">Updated</th>
                            <th class="px-11 py-3 text-left font-semibold w-1/4">Status</th>
                            <th class="px-12 py-3 text-left font-semibold w-1/4">Last Seen</th>
                            <th class="px-11 py-3 text-left font-semibold w-1/4">Status Updated At</th>
                            <th class="px-6 py-3 text-left font-semibold w-1/4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-green-200 dark:divide-green-800">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-2 text-xs">{{ $user->id }}</td>
                                <td class="px-4 py-2 text-xs">{{ $user->name }}</td>
                                <td class="px-4 py-2 text-xs">{{ $user->email }}</td>
                                <td class="px-4 py-2 text-xs">{{ $user->role?->name ?? 'Unknown' }}</td>
                                <td class="px-4 py-2 text-xs">{{ $user->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-4 py-2 text-xs">{{ $user->updated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-4 py-2 text-xs">{{ $user->status }}</td>
                                <td class="px-4 py-2 text-xs">
                                    @if (! empty($user->sessions_max_last_activity))
                                        {{ \Carbon\Carbon::createFromTimestamp((int) $user->sessions_max_last_activity)->diffForHumans() }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-xs">{{ $user->last_status_updated?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex space-x-2">
                                        @if ($user->id != 1)
                                            <button data-modal-target="userEdit-{{ $user->id }}" id="openUserModal"
                                                href="/components/user-edit-box"
                                                class="cursor-pointer bg-blue-500 text-white px-3 py-1 rounded text-sm underline hover:bg-blue-800">Edit</button>
                                            <button data-modal-target="userDeactivate-{{ $user->id }}" id="openUserModal"
                                                href="/components/user-deactivate-box"
                                                class="cursor-pointer {{ $user->status == 'Deactivated' ? 'bg-yellow-300 hover:bg-yellow-500 text-black' : 'bg-red-600 hover:bg-red-800 text-white'}} px-3 py-1 rounded text-sm underline cursor-pointer">{{ $user->status == 'Deactivated' ? 'Activate' : 'Deactivate'}}</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @include('components.user-edit-box', ['user' => $user])
                            @include('components.user-deactivate-box', ['user' => $user])
                        @endforeach
                        <!-- Add more rows as needed -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-user-add-box :roles="$roles" />
</x-layouts.app>
