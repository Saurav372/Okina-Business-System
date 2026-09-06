<x-layouts.admin title="Roles & Permissions" description="Inspect role archetypes, functional domain capabilities, and sensitive manager privileges.">
    <div class="space-y-6" x-data="{
        selectedRole: null,
        rolePermissions: [],
        openModal(role, permSlugs) {
            this.selectedRole = role;
            this.rolePermissions = permSlugs;
            $dispatch('open-overlay', 'edit-role-permissions-modal');
        }
    }">
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

        <!-- Roles Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($roles as $role)
                @php
                    $isSuperAdmin = $role->slug === \App\Models\Role::SUPER_ADMIN;
                    $rolePermsJson = htmlspecialchars(json_encode($role->permissions->pluck('slug')->all()), ENT_QUOTES, 'UTF-8');
                @endphp
                <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs p-5 flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                @if($isSuperAdmin)
                                    <div class="w-7 h-7 rounded-lg bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center">
                                        <x-icons.lucide name="lucide-shield-alert" class="w-4 h-4" />
                                    </div>
                                @else
                                    <div class="w-7 h-7 rounded-lg bg-neutral-100 border border-neutral-200 text-neutral-700 flex items-center justify-center">
                                        <x-icons.lucide name="lucide-shield" class="w-4 h-4" />
                                    </div>
                                @endif
                                <h3 class="text-sm font-black text-neutral-900">{{ $role->name }}</h3>
                            </div>
                            <span class="text-[11px] font-mono text-neutral-400 bg-neutral-50 px-2 py-0.5 rounded-md border border-neutral-100">
                                {{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}
                            </span>
                        </div>
                        <p class="text-xs text-neutral-500 leading-relaxed">{{ $role->description ?? 'Operational role defined for Okina platform.' }}</p>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex items-center justify-between">
                        @if($isSuperAdmin)
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-rose-600 bg-rose-50 px-2 py-1 rounded-md border border-rose-200">
                                <x-icons.lucide name="lucide-lock" class="w-3 h-3" />
                                <span>Universal Bypass</span>
                            </span>
                        @else
                            <span class="text-[11px] font-semibold text-neutral-600">
                                {{ $role->permissions->count() }} permissions
                            </span>
                            @if($isSuperAdmin || auth()->user()->hasRole(\App\Models\Role::SUPER_ADMIN))
                                <button type="button" @click="openModal({ id: {{ $role->id }}, name: '{{ addslashes($role->name) }}', slug: '{{ $role->slug }}' }, {{ $rolePermsJson }})" class="px-3 py-1 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-lg transition-colors shadow-xs">
                                    Edit Capabilities
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Interactive Collapsible Permission Matrix -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-grid" class="w-4 h-4 text-neutral-500" />
                    <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-800">Domain Capabilities Matrix</h2>
                </div>
                <div class="flex items-center gap-3 text-[11px] text-neutral-500">
                    <span class="inline-flex items-center gap-1 text-rose-600 font-bold">
                        <x-icons.lucide name="lucide-shield-alert" class="w-3.5 h-3.5" />
                        <span>Sensitive Manager Override</span>
                    </span>
                    <span class="text-neutral-300">|</span>
                    <span class="font-medium text-neutral-400">Click section header to collapse/expand</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-neutral-50 text-[10px] uppercase font-bold text-neutral-500 border-b border-neutral-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold min-w-[300px]">Permission Capability</th>
                            @foreach($roles as $role)
                                <th class="px-4 py-3 font-semibold text-center whitespace-nowrap">
                                    <div class="font-bold text-neutral-900">{{ $role->name }}</div>
                                    <div class="text-[9px] font-mono text-neutral-400 uppercase tracking-wider">{{ $role->slug }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 text-xs">
                        @foreach($groupedPermissions as $group => $domainPermissions)
                            <tr x-data="{ expanded: true }" class="bg-neutral-100/70 border-y border-neutral-200/80">
                                <td colspan="{{ 1 + $roles->count() }}" class="px-6 py-2.5 cursor-pointer select-none" @click="expanded = !expanded">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <x-icons.lucide name="lucide-chevron-right" class="w-3.5 h-3.5 text-neutral-500 transition-transform duration-200" ::class="{ 'rotate-90': expanded }" />
                                            <span class="font-black text-neutral-900 uppercase tracking-wider text-[11px]">{{ ucfirst($group) }} Domain</span>
                                            <span class="text-[10px] font-bold text-neutral-400 bg-white px-2 py-0.5 rounded-full border border-neutral-200">{{ $domainPermissions->count() }} capabilities</span>
                                        </div>
                                        <span class="text-[10px] text-neutral-400 font-semibold" x-text="expanded ? 'Collapse' : 'Expand'"></span>
                                    </div>
                                </td>
                            </tr>

                            @foreach($domainPermissions as $perm)
                                <tr x-show="expanded" class="hover:bg-neutral-50/70 transition-colors">
                                    <!-- Capability Info -->
                                    <td class="px-6 py-3.5">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-neutral-900 text-xs">{{ $perm->name }}</span>
                                                <span class="font-mono text-[10px] text-neutral-400 bg-neutral-100 px-1.5 py-0.5 rounded border border-neutral-200/60">{{ $perm->slug }}</span>
                                                @if($perm->is_sensitive)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                                        <x-icons.lucide name="lucide-shield-alert" class="w-2.5 h-2.5 text-rose-600" />
                                                        <span>Sensitive</span>
                                                    </span>
                                                @endif
                                            </div>
                                            @if($perm->description)
                                                <p class="text-[11px] text-neutral-500">{{ $perm->description }}</p>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Roles Status for this capability -->
                                    @foreach($roles as $role)
                                        <td class="px-4 py-3.5 text-center">
                                            @if($role->slug === \App\Models\Role::SUPER_ADMIN)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-rose-50 border border-rose-200 text-rose-600" title="Universal access managed by system">
                                                    <x-icons.lucide name="lucide-check" class="w-3.5 h-3.5" />
                                                </span>
                                            @elseif($role->permissions->contains('slug', $perm->slug))
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600">
                                                    <x-icons.lucide name="lucide-check" class="w-3.5 h-3.5" />
                                                </span>
                                            @else
                                                <span class="text-neutral-300 font-bold">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ======================= MODAL: EDIT ROLE PERMISSIONS ======================= -->
        <x-modal id="edit-role-permissions-modal" size="2xl">
            <form :action="'{{ url('admin/roles') }}/' + (selectedRole ? selectedRole.id : '')" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="border-b border-neutral-100 pb-3">
                    <div class="flex items-center gap-2">
                        <x-icons.lucide name="lucide-shield-check" class="w-5 h-5 text-neutral-800" />
                        <h3 class="text-base font-bold text-neutral-900">Configure Role Permissions</h3>
                    </div>
                    <p class="text-xs text-neutral-500 mt-0.5">Toggle authorized permissions for role: <span class="font-bold text-neutral-800" x-text="selectedRole?.name"></span></p>
                </div>

                <div class="space-y-6 max-h-[60vh] overflow-y-auto pr-2">
                    @foreach($groupedPermissions as $group => $domainPermissions)
                        <div class="space-y-2">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 pb-1 border-b border-neutral-100">
                                {{ ucfirst($group) }} Capabilities
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($domainPermissions as $perm)
                                    <label class="flex items-start gap-2.5 p-2.5 rounded-xl border border-neutral-200 hover:border-neutral-300 bg-neutral-50/50 hover:bg-neutral-50 cursor-pointer select-none transition-colors">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->slug }}" :checked="rolePermissions.includes('{{ $perm->slug }}')" class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900 mt-0.5">
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-1.5 font-bold text-xs text-neutral-900">
                                                <span>{{ $perm->name }}</span>
                                                @if($perm->is_sensitive)
                                                    <span class="inline-flex items-center gap-0.5 px-1 rounded text-[8px] font-bold uppercase bg-rose-100 text-rose-700">
                                                        Sensitive
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] font-mono text-neutral-400">{{ $perm->slug }}</div>
                                            @if($perm->description)
                                                <div class="text-[10px] text-neutral-500 leading-tight">{{ $perm->description }}</div>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-3 bg-neutral-100 rounded-xl text-neutral-700 text-[11px] leading-relaxed">
                    <span class="font-bold text-neutral-900">Audit Trail:</span> Any updates made to role permissions will record an immutable delta in the system security audit trail.
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-neutral-100">
                    <button type="button" @click="$dispatch('close-overlay', 'edit-role-permissions-modal')" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                        Save Role Capabilities
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.admin>
