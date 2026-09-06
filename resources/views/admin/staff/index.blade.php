<x-layouts.admin title="Staff & Access" description="Manage internal staff accounts, multi-role assignments, and authentication lifecycle.">
    <x-slot:header>
        <button type="button" @click="$dispatch('open-overlay', 'invite-staff-modal')" class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors">
            <x-icons.lucide name="lucide-user-plus" class="w-4 h-4 text-neutral-300" />
            <span>Invite Staff Member</span>
        </button>
    </x-slot:header>

    <div class="space-y-6" x-data="{
        editUser: null,
        rolesUser: null,
        statusUser: null,
        statusAction: '',
        openEdit(user) {
            this.editUser = user;
            $dispatch('open-overlay', 'edit-staff-modal');
        },
        openRoles(user) {
            this.rolesUser = user;
            $dispatch('open-overlay', 'roles-staff-modal');
        },
        confirmStatus(user, action) {
            this.statusUser = user;
            this.statusAction = action;
            $dispatch('open-overlay', 'status-confirm-modal');
        }
    }">
        <!-- Flash Alert / Invitation URL Copy Banner -->
        @if(session('invitation_url'))
            <div class="p-4 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-900 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 font-bold text-xs">
                        <x-icons.lucide name="lucide-mail" class="w-4 h-4 text-indigo-600" />
                        <span>Invitation Link Generated (Valid for 48 Hours)</span>
                    </div>
                    <p class="text-[11px] text-indigo-700 font-mono break-all">{{ session('invitation_url') }}</p>
                </div>
                <button type="button" onclick="navigator.clipboard.writeText('{{ session('invitation_url') }}'); alert('Invitation link copied to clipboard!');" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-colors whitespace-nowrap self-start sm:self-auto">
                    Copy Link
                </button>
            </div>
        @endif

        @if(session('status'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-xs flex items-center gap-2">
                <x-icons.lucide name="lucide-check-circle" class="w-4 h-4 text-emerald-600" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-xs space-y-1">
                <div class="flex items-center gap-1.5 font-bold">
                    <x-icons.lucide name="lucide-alert-triangle" class="w-4 h-4 text-rose-600" />
                    <span>Action Blocked</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px]">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Metrics Row -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-4 space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Total Staff</span>
                <div class="text-2xl font-black text-neutral-900">{{ $metrics['total'] }}</div>
            </div>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-4 space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Active</span>
                <div class="text-2xl font-black text-emerald-700">{{ $metrics['active'] }}</div>
            </div>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-4 space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-sky-600">Invited</span>
                <div class="text-2xl font-black text-sky-700">{{ $metrics['invited'] }}</div>
            </div>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-4 space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Suspended</span>
                <div class="text-2xl font-black text-rose-700">{{ $metrics['suspended'] }}</div>
            </div>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-4 space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">System Roles</span>
                <div class="text-2xl font-black text-neutral-900">{{ $metrics['roles_count'] }}</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-5">
            <form method="GET" action="{{ route('admin.staff.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="search" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Search Staff</label>
                        <input type="text" name="search" id="search" value="{{ $filters['search'] }}" placeholder="Search by name or email..." class="w-full text-xs rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5">
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Filter by Role</label>
                        <select name="role" id="role" class="w-full text-xs font-semibold rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5">
                            <option value="">All Roles</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->slug }}" {{ $filters['role'] === $r->slug ? 'selected' : '' }}>{{ $r->name }} ({{ $r->users_count }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Account Status</label>
                        <select name="status" id="status" class="w-full text-xs font-semibold rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5">
                            <option value="">All Statuses</option>
                            <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="invited" {{ $filters['status'] === 'invited' ? 'selected' : '' }}>Invited</option>
                            <option value="suspended" {{ $filters['status'] === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="locked" {{ $filters['status'] === 'locked' ? 'selected' : '' }}>Security Locked</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-neutral-100">
                    @if(request()->hasAny(['search', 'role', 'status']))
                        <a href="{{ route('admin.staff.index') }}" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors">
                            Reset Filters
                        </a>
                    @endif
                    <button type="submit" class="px-5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Staff Members Table -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-users" class="w-4 h-4 text-neutral-500" />
                    <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-800">Staff Accounts Directory</h2>
                </div>
                <span class="text-[11px] text-neutral-400 font-medium">Internal platform operators</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-neutral-50 text-[10px] uppercase font-bold text-neutral-500 border-b border-neutral-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Staff Member</th>
                            <th class="px-6 py-3 font-semibold">Assigned Roles</th>
                            <th class="px-6 py-3 font-semibold">Account Status</th>
                            <th class="px-6 py-3 font-semibold">Last Login</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 text-xs">
                        @forelse($staffMembers as $member)
                            @php
                                $isSuperAdmin = $member->hasRole(\App\Models\Role::SUPER_ADMIN);
                                $isLocked = $member->isSecurityLocked();
                                $rolesJson = htmlspecialchars(json_encode($member->roles->pluck('slug')->all()), ENT_QUOTES, 'UTF-8');
                            @endphp
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <!-- Staff Member Profile -->
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-neutral-900 text-white font-bold flex items-center justify-center text-xs flex-shrink-0">
                                            {{ $member->initials() }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-neutral-900">{{ $member->name }}</div>
                                            <div class="text-[11px] text-neutral-500 font-mono">{{ $member->email }}</div>
                                            @if($member->phone)
                                                <div class="text-[10px] text-neutral-400">{{ $member->phone }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Roles -->
                                <td class="px-6 py-3.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @forelse($member->roles as $role)
                                            @if($role->slug === \App\Models\Role::SUPER_ADMIN)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                                    <x-icons.lucide name="lucide-shield-alert" class="w-3 h-3 text-rose-600" />
                                                    <span>{{ $role->name }}</span>
                                                </span>
                                            @elseif($role->slug === \App\Models\Role::ADMIN)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    <x-icons.lucide name="lucide-shield" class="w-3 h-3 text-indigo-600" />
                                                    <span>{{ $role->name }}</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                    <span>{{ $role->name }}</span>
                                                </span>
                                            @endif
                                        @empty
                                            <span class="text-neutral-400 text-[11px] italic">No roles assigned</span>
                                        @endforelse
                                    </div>
                                </td>

                                <!-- Account Status -->
                                <td class="px-6 py-3.5">
                                    @if($isLocked)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                            <x-icons.lucide name="lucide-lock" class="w-3 h-3 text-amber-600" />
                                            <span>Locked</span>
                                        </span>
                                    @elseif($member->status === \App\Models\User::STATUS_ACTIVE)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Active</span>
                                        </span>
                                    @elseif($member->status === \App\Models\User::STATUS_INVITED)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-sky-50 text-sky-700 border border-sky-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                            <span>Invited</span>
                                        </span>
                                    @elseif($member->status === \App\Models\User::STATUS_SUSPENDED)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span>Suspended</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Last Login -->
                                <td class="px-6 py-3.5 font-mono text-neutral-600">
                                    @if($member->last_login_at)
                                        <div class="font-medium text-neutral-800">{{ $member->last_login_at->diffForHumans() }}</div>
                                        @if($member->last_login_ip)
                                            <div class="text-[10px] text-neutral-400">{{ $member->last_login_ip }}</div>
                                        @endif
                                    @else
                                        <span class="text-neutral-400">Never</span>
                                    @endif
                                </td>

                                <!-- Actions Dropdown / Quick Links -->
                                <td class="px-6 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" @click="openEdit({ id: {{ $member->id }}, name: '{{ addslashes($member->name) }}', phone: '{{ addslashes($member->phone ?? '') }}' })" class="px-2.5 py-1 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-lg transition-colors">
                                            Edit
                                        </button>
                                        <button type="button" @click="openRoles({ id: {{ $member->id }}, name: '{{ addslashes($member->name) }}', roles: {{ $rolesJson }} })" class="px-2.5 py-1 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-lg transition-colors">
                                            Roles
                                        </button>

                                        @if($isLocked)
                                            <button type="button" @click="confirmStatus({ id: {{ $member->id }}, name: '{{ addslashes($member->name) }}' }, 'unlock')" class="px-2.5 py-1 bg-amber-100 hover:bg-amber-200 text-amber-800 text-xs font-bold rounded-lg transition-colors">
                                                Unlock
                                            </button>
                                        @elseif($member->status === \App\Models\User::STATUS_SUSPENDED)
                                            <button type="button" @click="confirmStatus({ id: {{ $member->id }}, name: '{{ addslashes($member->name) }}' }, 'reactivate')" class="px-2.5 py-1 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-bold rounded-lg transition-colors">
                                                Reactivate
                                            </button>
                                        @elseif($member->status === \App\Models\User::STATUS_ACTIVE)
                                            @if($member->id !== auth()->id())
                                                <button type="button" @click="confirmStatus({ id: {{ $member->id }}, name: '{{ addslashes($member->name) }}' }, 'suspend')" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-lg transition-colors">
                                                    Suspend
                                                </button>
                                            @endif
                                        @elseif($member->status === \App\Models\User::STATUS_INVITED)
                                            <form method="POST" action="{{ route('admin.staff.resend_invitation', $member->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 bg-sky-50 hover:bg-sky-100 text-sky-700 text-xs font-bold rounded-lg transition-colors">
                                                    Resend
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-neutral-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <div class="w-10 h-10 rounded-full bg-neutral-100 flex items-center justify-center text-neutral-400">
                                            <x-icons.lucide name="lucide-users" class="w-5 h-5" />
                                        </div>
                                        <p class="text-sm font-semibold text-neutral-700">No staff members found</p>
                                        <p class="text-xs text-neutral-400">Try adjusting your filters or invite a new staff member.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($staffMembers->hasPages())
                <div class="px-6 py-4 border-t border-neutral-200 bg-neutral-50/50">
                    {{ $staffMembers->links() }}
                </div>
            @endif
        </div>

        <!-- ======================= MODALS ======================= -->

        <!-- 1. Invite Staff Modal -->
        <x-modal id="invite-staff-modal" size="md">
            <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-5">
                @csrf
                <div class="border-b border-neutral-100 pb-3">
                    <h3 class="text-base font-bold text-neutral-900">Invite Staff Member</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Send an expiring invitation link for the staff member to set up their password.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="invite_name" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1">Full Name</label>
                        <input type="text" name="name" id="invite_name" required placeholder="e.g. Aman Sharma" class="w-full text-xs rounded-xl border-neutral-300 py-2.5 px-3">
                    </div>

                    <div>
                        <label for="invite_email" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1">Work Email</label>
                        <input type="email" name="email" id="invite_email" required placeholder="staff@okinadesigning.in" class="w-full text-xs rounded-xl border-neutral-300 py-2.5 px-3">
                    </div>

                    <div>
                        <label for="invite_phone" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1">Phone Number (Optional)</label>
                        <input type="text" name="phone" id="invite_phone" placeholder="+91 9876543210" class="w-full text-xs rounded-xl border-neutral-300 py-2.5 px-3">
                    </div>

                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1.5">Assign System Roles</span>
                        <div class="space-y-2 border border-neutral-200 rounded-xl p-3 max-h-48 overflow-y-auto">
                            @foreach($roles as $role)
                                <label class="flex items-start gap-2.5 text-xs text-neutral-800 cursor-pointer select-none">
                                    <input type="checkbox" name="roles[]" value="{{ $role->slug }}" class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900 mt-0.5">
                                    <div>
                                        <div class="font-bold {{ $role->slug === \App\Models\Role::SUPER_ADMIN ? 'text-rose-600' : 'text-neutral-900' }}">{{ $role->name }}</div>
                                        @if($role->description)
                                            <div class="text-[11px] text-neutral-400">{{ $role->description }}</div>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-neutral-100">
                    <button type="button" @click="$dispatch('close-overlay', 'invite-staff-modal')" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                        Generate &amp; Send Invitation
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- 2. Edit Profile Modal -->
        <x-modal id="edit-staff-modal" size="md">
            <form :action="'{{ url('admin/staff') }}/' + (editUser ? editUser.id : '')" method="POST" class="space-y-5">
                @csrf
                @method('PATCH')

                <div class="border-b border-neutral-100 pb-3">
                    <h3 class="text-base font-bold text-neutral-900">Edit Staff Details</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Update personal identity details for <span class="font-bold text-neutral-800" x-text="editUser?.name"></span>.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="edit_name" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1">Full Name</label>
                        <input type="text" name="name" id="edit_name" required :value="editUser?.name" class="w-full text-xs rounded-xl border-neutral-300 py-2.5 px-3">
                    </div>

                    <div>
                        <label for="edit_phone" class="block text-xs font-bold uppercase tracking-wider text-neutral-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" id="edit_phone" :value="editUser?.phone" class="w-full text-xs rounded-xl border-neutral-300 py-2.5 px-3">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-neutral-100">
                    <button type="button" @click="$dispatch('close-overlay', 'edit-staff-modal')" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                        Save Changes
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- 3. Manage Roles Modal -->
        <x-modal id="roles-staff-modal" size="md">
            <form :action="'{{ url('admin/staff') }}/' + (rolesUser ? rolesUser.id : '') + '/roles'" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="border-b border-neutral-100 pb-3">
                    <h3 class="text-base font-bold text-neutral-900">Manage Staff Roles</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Select multi-role assignments for <span class="font-bold text-neutral-800" x-text="rolesUser?.name"></span>.</p>
                </div>

                <div class="space-y-2 border border-neutral-200 rounded-xl p-3 max-h-64 overflow-y-auto">
                    @foreach($roles as $role)
                        <label class="flex items-start gap-2.5 text-xs text-neutral-800 cursor-pointer select-none">
                            <input type="checkbox" name="roles[]" value="{{ $role->slug }}" :checked="rolesUser?.roles?.includes('{{ $role->slug }}')" class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900 mt-0.5">
                            <div>
                                <div class="font-bold {{ $role->slug === \App\Models\Role::SUPER_ADMIN ? 'text-rose-600' : 'text-neutral-900' }}">{{ $role->name }}</div>
                                @if($role->description)
                                    <div class="text-[11px] text-neutral-400">{{ $role->description }}</div>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-[11px]">
                    <span class="font-bold">Security Notice:</span> Modifying roles immediately recalculates authorization permissions and terminates active sessions if privileges are revoked.
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-neutral-100">
                    <button type="button" @click="$dispatch('close-overlay', 'roles-staff-modal')" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                        Update Roles
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- 4. Status Confirmation Modal -->
        <x-modal id="status-confirm-modal" size="md">
            <form :action="'{{ url('admin/staff') }}/' + (statusUser ? statusUser.id : '') + '/status'" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="action" :value="statusAction">

                <div class="flex items-center gap-3 border-b border-neutral-100 pb-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="{
                        'bg-rose-100 text-rose-600': statusAction === 'suspend',
                        'bg-emerald-100 text-emerald-600': statusAction === 'reactivate',
                        'bg-amber-100 text-amber-600': statusAction === 'unlock'
                    }">
                        <x-icons.lucide name="lucide-shield-alert" class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-neutral-900" x-text="statusAction === 'suspend' ? 'Suspend Staff Account' : (statusAction === 'reactivate' ? 'Reactivate Staff Account' : 'Clear Security Lockout')"></h3>
                        <p class="text-xs text-neutral-500">Target: <span class="font-bold text-neutral-800" x-text="statusUser?.name"></span></p>
                    </div>
                </div>

                <div class="text-xs text-neutral-600 leading-relaxed" x-text="statusAction === 'suspend' 
                    ? 'Suspending this account will immediately revoke all dashboard privileges and destroy active login sessions.' 
                    : (statusAction === 'reactivate' ? 'Reactivating will restore normal operational dashboard access.' : 'Unlocking will reset the security attempt counter and clear the lockout cooldown.')">
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-neutral-100">
                    <button type="button" @click="$dispatch('close-overlay', 'status-confirm-modal')" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 text-white text-xs font-bold rounded-xl transition-colors shadow-xs" :class="{
                        'bg-rose-600 hover:bg-rose-700': statusAction === 'suspend',
                        'bg-emerald-600 hover:bg-emerald-700': statusAction === 'reactivate',
                        'bg-amber-600 hover:bg-amber-700': statusAction === 'unlock'
                    }" x-text="statusAction === 'suspend' ? 'Confirm Suspension' : (statusAction === 'reactivate' ? 'Confirm Reactivation' : 'Confirm Unlock')">
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.admin>
