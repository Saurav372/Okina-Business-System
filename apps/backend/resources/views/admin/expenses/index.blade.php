<x-layouts.admin title="Operational Expenses">
    <div class="space-y-6" x-data="{
        recordModalOpen: {{ $errors->expense->any() || ($modalState['expense_modal_mode'] ?? '') === 'edit' ? 'true' : 'false' }},
        categoryModalOpen: {{ $errors->category->any() ? 'true' : 'false' }},
        rejectModalOpen: {{ $errors->rejection->any() ? 'true' : 'false' }},
        activeExpense: null,
        rejectionReason: '',

        openRejectModal(expense) {
            this.activeExpense = expense;
            this.rejectionReason = '';
            this.rejectModalOpen = true;
        }
    }">

        <!-- Header & Action Buttons -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-neutral-200 pb-3 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 tracking-tight">Operational Expenses</h1>
                <p class="text-xs text-neutral-500 mt-1">Track business expenditure, category allocations, proof attachments, and approval workflows.</p>
            </div>
            <div class="flex items-center gap-2">
                @can('viewExpenseReports', \App\Models\Expense::class)
                    <a href="{{ route('admin.expenses.export', request()->query()) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-neutral-300 text-neutral-700 rounded-xl hover:bg-neutral-50 transition-colors shadow-xs">
                        <x-icons.lucide name="lucide-download" class="w-4 h-4 text-neutral-500" />
                        <span>Export CSV</span>
                    </a>
                @endcan

                @can('create', \App\Models\ExpenseCategory::class)
                    <button type="button"
                            @click="categoryModalOpen = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-neutral-100 text-neutral-700 rounded-xl hover:bg-neutral-200 transition-colors shadow-xs">
                        <x-icons.lucide name="lucide-folder-tree" class="w-4 h-4 text-neutral-600" />
                        <span>Categories</span>
                    </button>
                @endcan

                @can('create', \App\Models\Expense::class)
                    <button type="button"
                            @click="recordModalOpen = true"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors shadow-xs">
                        <x-icons.lucide name="lucide-plus" class="w-4 h-4" />
                        <span>Record Expense</span>
                    </button>
                @endcan
            </div>
        </div>

        <!-- Session Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3 shadow-xs">
                <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4 flex-shrink-0 text-emerald-600" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-medium space-y-1 shadow-xs">
                @foreach ($errors->all() as $error)
                    <div class="flex items-center gap-2">
                        <x-icons.lucide name="lucide-alert-circle" class="w-4 h-4 flex-shrink-0 text-red-600" />
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- KPI Metrics Grid (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Approved Expenses -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Total Approved Expenses</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <x-icons.lucide name="lucide-indian-rupee" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold font-mono text-emerald-600">₹{{ number_format(($metrics['total_approved_expenses_minor'] ?? 0) / 100, 2) }}</div>
                <div class="mt-1 text-xs text-emerald-700/80">Approved operational expenditure</div>
            </div>

            <!-- Pending Approvals -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Pending Approvals</span>
                    <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600 border border-amber-100">
                        <x-icons.lucide name="lucide-clock" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold font-mono text-amber-600">{{ number_format($metrics['pending_count'] ?? 0) }}</div>
                <div class="mt-1 text-xs text-amber-700/80">Awaiting manager authorization</div>
            </div>

            <!-- Approved Count -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Approved Count</span>
                    <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600 border border-blue-100">
                        <x-icons.lucide name="lucide-check-circle" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold font-mono text-blue-600">{{ number_format($metrics['approved_count'] ?? 0) }}</div>
                <div class="mt-1 text-xs text-blue-700/80">Approved expense records</div>
            </div>

            <!-- Active Categories -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-wider">Active Categories</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-layers" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold font-mono text-neutral-900">{{ number_format($metrics['global_active_categories_count'] ?? 0) }}</div>
                <div class="mt-1 text-xs text-neutral-500">Active expenditure categories</div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-xs">
            <form method="GET" action="{{ route('admin.expenses.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search -->
                <div>
                    <label class="block text-[11px] font-semibold text-neutral-600 uppercase mb-1">Search</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ $filters->search }}" placeholder="ID, reference, notes..."
                               class="w-full text-xs rounded-xl border-neutral-300 pl-8 focus:border-[color:var(--color-brand-500)] focus:ring-[color:var(--color-brand-500)]" />
                        <x-icons.lucide name="lucide-search" class="w-4 h-4 absolute left-2.5 top-2.5 text-neutral-400" />
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-[11px] font-semibold text-neutral-600 uppercase mb-1">Status</label>
                    <select name="status" class="w-full text-xs rounded-xl border-neutral-300 focus:border-[color:var(--color-brand-500)] focus:ring-[color:var(--color-brand-500)]">
                        <option value="">All Statuses</option>
                        @foreach (\App\Models\Expense::STATUS_DRAFT ? ['draft' => 'Draft', 'pending_approval' => 'Pending Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'] : [] as $val => $label)
                            <option value="{{ $val }}" {{ $filters->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-[11px] font-semibold text-neutral-600 uppercase mb-1">Category</label>
                    <select name="category_public_id" class="w-full text-xs rounded-xl border-neutral-300 focus:border-[color:var(--color-brand-500)] focus:ring-[color:var(--color-brand-500)]">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->public_id }}" {{ $filters->categoryPublicId === $cat->public_id ? 'selected' : '' }}>{{ $cat->name }} ({{ $cat->code }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-[11px] font-semibold text-neutral-600 uppercase mb-1">Date From</label>
                    <input type="date" name="date_from" value="{{ $filters->dateFrom }}"
                           class="w-full text-xs rounded-xl border-neutral-300 focus:border-[color:var(--color-brand-500)] focus:ring-[color:var(--color-brand-500)]" />
                </div>

                <!-- Action buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full py-2 px-3 text-xs font-bold bg-neutral-900 text-white rounded-xl hover:bg-neutral-800 transition-colors shadow-xs">
                        Filter
                    </button>
                    <a href="{{ route('admin.expenses.index') }}" class="py-2 px-3 text-xs font-semibold bg-neutral-100 text-neutral-700 rounded-xl hover:bg-neutral-200 transition-colors">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Expenses Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-200 text-[11px] font-bold text-neutral-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Expense ID</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Reference / Notes</th>
                            <th class="py-3 px-4">Proof</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 font-medium">
                        @forelse ($expenses as $expense)
                            <tr class="hover:bg-neutral-50/50 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-neutral-900">
                                    {{ $expense->public_id }}
                                </td>
                                <td class="py-3 px-4 text-neutral-600">
                                    {{ $expense->occurred_at?->format('Y-m-d') }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-700 font-semibold text-[11px]">
                                        {{ $expense->expenseCategory?->name ?: 'N/A' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-neutral-900">
                                    ₹{{ number_format($expense->amount_minor / 100, 2) }}
                                </td>
                                <td class="py-3 px-4 text-neutral-600 max-w-xs truncate">
                                    @if ($expense->reference)
                                        <span class="font-semibold text-neutral-800">Ref: {{ $expense->reference }}</span><br/>
                                    @endif
                                    <span>{{ Str::limit($expense->notes ?: '—', 40) }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    @if ($expense->attachment)
                                        <a href="{{ route('admin.expenses.attachments.download', ['expense' => $expense->public_id, 'attachment' => $expense->attachment->public_id]) }}"
                                           class="inline-flex items-center gap-1 text-[11px] font-medium text-blue-600 hover:underline">
                                            <x-icons.lucide name="lucide-paperclip" class="w-3.5 h-3.5" />
                                            <span>Proof</span>
                                        </a>
                                    @else
                                        <span class="text-neutral-400 text-[11px]">None</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if ($expense->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px] uppercase border border-emerald-200">
                                            Approved
                                        </span>
                                    @elseif ($expense->status === 'pending_approval')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-bold text-[10px] uppercase border border-amber-200">
                                            Pending Approval
                                        </span>
                                    @elseif ($expense->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-50 text-red-700 font-bold text-[10px] uppercase border border-red-200">
                                            Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-700 font-bold text-[10px] uppercase border border-neutral-200">
                                            Draft
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right space-x-1">
                                    @if (in_array($expense->status, ['draft', 'rejected'], true))
                                        @can('submit', $expense)
                                            <form method="POST" action="{{ route('admin.expenses.submit', $expense->public_id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 py-1 text-[11px] font-semibold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                                                    Submit
                                                </button>
                                            </form>
                                        @endcan
                                    @endif

                                    @if ($expense->status === 'pending_approval')
                                        @can('approve', $expense)
                                            <form method="POST" action="{{ route('admin.expenses.approve', $expense->public_id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 py-1 text-[11px] font-semibold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                                                    Approve
                                                </button>
                                            </form>
                                        @endcan

                                        @can('reject', $expense)
                                            <button type="button" @click="openRejectModal({{ json_encode($expense) }})"
                                                    class="px-2 py-1 text-[11px] font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                                Reject
                                            </button>
                                        @endcan

                                        @can('withdraw', $expense)
                                            <form method="POST" action="{{ route('admin.expenses.withdraw', $expense->public_id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 py-1 text-[11px] font-semibold bg-neutral-200 text-neutral-800 rounded-lg hover:bg-neutral-300 transition-colors">
                                                    Withdraw
                                                </button>
                                            </form>
                                        @endcan
                                    @endif

                                    @if (in_array($expense->status, ['draft', 'rejected'], true))
                                        @can('delete', $expense)
                                            <form method="POST" action="{{ route('admin.expenses.destroy', $expense->public_id) }}" class="inline" onsubmit="return confirm('Delete this draft expense?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2 py-1 text-[11px] font-semibold bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-neutral-400 text-xs">
                                    No operational expense records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($expenses->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>

        <!-- Record Expense Modal -->
        <div x-show="recordModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-neutral-200" @click.away="recordModalOpen = false">
                <div class="flex items-center justify-between border-b border-neutral-200 pb-3 mb-4">
                    <h3 class="text-lg font-bold text-neutral-900">Record Operational Expense</h3>
                    <button type="button" @click="recordModalOpen = false" class="text-neutral-400 hover:text-neutral-600">
                        <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.expenses.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 uppercase mb-1">Expense Category *</label>
                        <select name="expense_category_public_id" required class="w-full text-xs rounded-xl border-neutral-300">
                            <option value="">Select Category</option>
                            @foreach ($categories->where('is_active', true) as $cat)
                                <option value="{{ $cat->public_id }}">{{ $cat->name }} ({{ $cat->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 uppercase mb-1">Amount (INR ₹) *</label>
                            <input type="text" name="amount" placeholder="e.g. 250.50" required class="w-full text-xs rounded-xl border-neutral-300" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 uppercase mb-1">Date *</label>
                            <input type="date" name="occurred_at" value="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-xl border-neutral-300" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 uppercase mb-1">Reference Number</label>
                        <input type="text" name="reference" placeholder="Invoice / Receipt / Voucher ID" class="w-full text-xs rounded-xl border-neutral-300" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 uppercase mb-1">Notes / Description</label>
                        <textarea name="notes" rows="3" placeholder="Purpose or context..." class="w-full text-xs rounded-xl border-neutral-300"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 uppercase mb-1">Proof Attachment (Max 10MB PDF/Image)</label>
                        <input type="file" name="proof_file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="w-full text-xs rounded-xl border-neutral-300" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-200">
                        <button type="button" @click="recordModalOpen = false" class="px-4 py-2 text-xs font-semibold text-neutral-700 bg-neutral-100 rounded-xl hover:bg-neutral-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-[color:var(--color-brand-600)] rounded-xl hover:bg-[color:var(--color-brand-700)] shadow-xs">
                            Save Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Rejection Reason Modal -->
        <div x-show="rejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-neutral-200" @click.away="rejectModalOpen = false">
                <div class="flex items-center justify-between border-b border-neutral-200 pb-3 mb-4">
                    <h3 class="text-lg font-bold text-neutral-900">Reject Expense</h3>
                    <button type="button" @click="rejectModalOpen = false" class="text-neutral-400 hover:text-neutral-600">
                        <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                    </button>
                </div>

                <form x-bind:action="activeExpense ? '/admin/expenses/' + activeExpense.public_id + '/reject' : ''" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 uppercase mb-1">Rejection Reason *</label>
                        <textarea name="rejection_reason" x-model="rejectionReason" rows="3" required placeholder="Specify reason for rejection (min 5 chars)..." class="w-full text-xs rounded-xl border-neutral-300"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-200">
                        <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 text-xs font-semibold text-neutral-700 bg-neutral-100 rounded-xl hover:bg-neutral-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 shadow-xs">
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Category Management Modal -->
        <div x-show="categoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl border border-neutral-200" @click.away="categoryModalOpen = false">
                <div class="flex items-center justify-between border-b border-neutral-200 pb-3 mb-4">
                    <h3 class="text-lg font-bold text-neutral-900">Expense Categories</h3>
                    <button type="button" @click="categoryModalOpen = false" class="text-neutral-400 hover:text-neutral-600">
                        <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Add Category Form -->
                <form method="POST" action="{{ route('admin.expense_categories.store') }}" class="mb-6 space-y-3 bg-neutral-50 p-4 rounded-xl border border-neutral-200">
                    @csrf
                    <div class="text-xs font-bold text-neutral-800 uppercase">Create New Category</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <input type="text" name="name" placeholder="Category Name *" required class="w-full text-xs rounded-xl border-neutral-300" />
                        </div>
                        <div>
                            <input type="text" name="code" placeholder="Machine Code e.g. UTILITIES *" required class="w-full text-xs rounded-xl border-neutral-300 uppercase" />
                        </div>
                    </div>
                    <div>
                        <input type="text" name="description" placeholder="Description (optional)" class="w-full text-xs rounded-xl border-neutral-300" />
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-3 py-1.5 text-xs font-bold bg-neutral-900 text-white rounded-xl hover:bg-neutral-800">
                            Add Category
                        </button>
                    </div>
                </form>

                <!-- Existing Categories Table -->
                <div class="max-h-60 overflow-y-auto border border-neutral-200 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-neutral-100 text-neutral-600 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="p-2.5">Name</th>
                                <th class="p-2.5">Code</th>
                                <th class="p-2.5">Status</th>
                                <th class="p-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 font-medium">
                            @foreach ($categories as $cat)
                                <tr class="hover:bg-neutral-50">
                                    <td class="p-2.5 font-bold text-neutral-900">{{ $cat->name }}</td>
                                    <td class="p-2.5 font-mono text-neutral-600">{{ $cat->code }}</td>
                                    <td class="p-2.5">
                                        @if ($cat->is_active)
                                            <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[10px]">Active</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-500 font-bold text-[10px]">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="p-2.5 text-right space-x-1">
                                        <form method="POST" action="{{ route('admin.expense_categories.toggle_active', $cat->public_id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[11px] font-semibold text-blue-600 hover:underline">
                                                {{ $cat->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.expense_categories.destroy', $cat->public_id) }}" class="inline" onsubmit="return confirm('Delete category {{ $cat->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[11px] font-semibold text-red-600 hover:underline">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>
