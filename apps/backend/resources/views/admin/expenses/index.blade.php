<x-layouts.admin title="Operational Expenses">
    <div class="space-y-6" x-data="{
        recordModalOpen: false,
        rejectModalOpen: false,
        activeExpense: null,
        rejectionReason: '',

        openRejectModal(expense) {
            this.activeExpense = expense;
            this.rejectionReason = '';
            this.rejectModalOpen = true;
        }
    }">

        <!-- Header & Action -->
        <div class="flex items-center justify-between border-b border-neutral-200 pb-3 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 tracking-tight">Operational Expenses</h1>
                <p class="text-xs text-neutral-500 mt-1">Track business expenditure, category allocations, and approval workflows.</p>
            </div>
            @can('create', \App\Models\Expense::class)
                <button type="button"
                        @click="recordModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors shadow-xs">
                    <x-icons.lucide name="lucide-plus" class="w-4 h-4" />
                    <span>Record Expense</span>
                </button>
            @endcan
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
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold font-mono text-emerald-600">₹{{ number_format($metrics->totalApprovedMinor / 100, 2) }}</div>
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
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold font-mono text-amber-600">{{ number_format($metrics->pendingApprovalCount) }}</div>
                <div class="mt-1 text-xs text-amber-700/80">Awaiting manager authorization</div>
            </div>

            <!-- Rejected Expenses -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-red-600 uppercase tracking-wider">Rejected Expenses</span>
                    <div class="p-2.5 rounded-xl bg-red-50 text-red-600 border border-red-100">
                        <x-icons.lucide name="lucide-x-circle" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold font-mono text-red-600">{{ number_format($metrics->rejectedCount) }}</div>
                <div class="mt-1 text-xs text-red-700/80">Rejected by manager</div>
            </div>

            <!-- Total Recorded -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Recorded</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-receipt" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold font-mono text-neutral-900">{{ number_format($metrics->totalExpensesCount) }}</div>
                <div class="mt-1 text-xs text-neutral-500">In active filter scope</div>
            </div>
        </div>

        <!-- Filter Bar Form -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.expenses.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                    </div>
                    <input type="text" name="search" value="{{ $filters->search }}"
                           placeholder="Search reference, notes, public ID, category..."
                           class="w-full pl-9 pr-4 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Category Selector -->
                <div>
                    <select name="category_id" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ $filters->categoryId === null ? 'selected' : '' }}>All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $filters->categoryId === $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Selector -->
                <div>
                    <select name="status" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ $filters->status === '' ? 'selected' : '' }}>All Statuses</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" {{ $filters->status === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors">Filter</button>
                </div>
            </form>
        </div>

        <!-- Expenses Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Expense ID</th>
                            <th class="py-3.5 px-4 font-semibold">Category</th>
                            <th class="py-3.5 px-4 font-semibold">Occurred Date</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Amount</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold">Reference &amp; Notes</th>
                            <th class="py-3.5 px-4 font-semibold">Recorded By</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($expenses as $expense)
                            @php
                                $statusEnum = \App\Enums\ExpenseStatus::tryFrom($expense->status);
                            @endphp
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <!-- Public ID -->
                                <td class="py-3.5 px-4 font-mono font-bold text-[color:var(--color-brand-600)]">
                                    {{ $expense->public_id }}
                                </td>

                                <!-- Category -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-neutral-900">{{ $expense->expenseCategory?->name ?? 'Unassigned' }}</div>
                                    <div class="text-[11px] font-mono text-neutral-400 mt-0.5">{{ $expense->expenseCategory?->code }}</div>
                                </td>

                                <!-- Occurred Date -->
                                <td class="py-3.5 px-4 font-mono text-neutral-700">
                                    {{ $expense->occurred_at ? $expense->occurred_at->format('Y-m-d') : '—' }}
                                </td>

                                <!-- Amount -->
                                <td class="py-3.5 px-4 text-right font-mono font-extrabold text-neutral-900">
                                    ₹{{ number_format($expense->amount_minor / 100, 2) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $statusEnum?->badgeClass() ?? 'bg-neutral-100 text-neutral-700 border-neutral-200' }}">
                                        {{ $statusEnum?->label() ?? ucfirst($expense->status) }}
                                    </span>
                                </td>

                                <!-- Reference & Notes -->
                                <td class="py-3.5 px-4 text-neutral-600">
                                    @if ($expense->reference)
                                        <div class="font-mono font-medium text-neutral-800">{{ $expense->reference }}</div>
                                    @endif
                                    @if ($expense->notes)
                                        <div class="text-[11px] text-neutral-500 truncate max-w-xs">{{ $expense->notes }}</div>
                                    @endif
                                    @if (!$expense->reference && !$expense->notes)
                                        <span class="text-neutral-300 italic">—</span>
                                    @endif
                                </td>

                                <!-- Recorded By -->
                                <td class="py-3.5 px-4 text-neutral-600">
                                    {{ $expense->recordedBy?->name ?? 'System' }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- Submit Action --}}
                                        @if (in_array($expense->status, [\App\Models\Expense::STATUS_DRAFT, \App\Models\Expense::STATUS_REJECTED], true))
                                            @can('submit', $expense)
                                                <form action="{{ route('admin.expenses.submit', $expense) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-2xs transition-colors">
                                                        <x-icons.lucide name="lucide-send" class="w-3.5 h-3.5" />
                                                        <span>Submit</span>
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif

                                        {{-- Approve & Reject Actions --}}
                                        @if ($expense->status === \App\Models\Expense::STATUS_PENDING_APPROVAL)
                                            @can('approve', $expense)
                                                <form action="{{ route('admin.expenses.approve', $expense) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-2xs transition-colors">
                                                        <x-icons.lucide name="lucide-check-circle" class="w-3.5 h-3.5" />
                                                        <span>Approve</span>
                                                    </button>
                                                </form>
                                            @endcan
                                            @can('reject', $expense)
                                                <button type="button"
                                                    data-expense="{{ json_encode(['id' => $expense->public_id, 'amount' => number_format($expense->amount_minor / 100, 2)]) }}"
                                                    @click="openRejectModal(JSON.parse($el.dataset.expense))"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-2xs transition-colors">
                                                    <x-icons.lucide name="lucide-x-circle" class="w-3.5 h-3.5" />
                                                    <span>Reject</span>
                                                </button>
                                            @endcan
                                        @endif

                                        {{-- Delete Action --}}
                                        @if ($expense->status !== \App\Models\Expense::STATUS_APPROVED)
                                            @can('delete', $expense)
                                                <form action="{{ route('admin.expenses.destroy', $expense) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this draft/rejected expense?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1 text-neutral-400 hover:text-red-600 transition-colors">
                                                        <x-icons.lucide name="lucide-trash-2" class="w-4 h-4" />
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-receipt" class="w-10 h-10 mx-auto text-neutral-300 mb-3" />
                                    <p class="text-sm font-semibold text-neutral-700">No operational expenses found</p>
                                    <p class="text-xs text-neutral-400 mt-1">Try adjusting filter parameters or click "Record Expense" above.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Server-Side Pagination -->
            @if ($expenses->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>

        <!-- Record Expense Modal -->
        <div x-show="recordModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="recordModalOpen = false" class="fixed inset-0 bg-neutral-950/40 backdrop-blur-xs transition-opacity" aria-hidden="true"></div>

                <div class="inline-block align-bottom bg-white border border-neutral-200 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <form action="{{ route('admin.expenses.store') }}" method="POST" class="p-6 space-y-4">
                        @csrf

                        <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                            <div>
                                <h3 class="text-base font-bold text-neutral-900">Record Operational Expense</h3>
                                <p class="text-xs text-neutral-500 mt-0.5">Enter expenditure details for authorization.</p>
                            </div>
                            <button type="button" @click="recordModalOpen = false" class="text-neutral-400 hover:text-neutral-600">
                                <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Category Selection -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Expense Category</label>
                            <select name="expense_category_public_id" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->public_id }}">{{ $cat->name }} ({{ $cat->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Amount Input -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Amount (₹)</label>
                            <input type="number"
                                   name="amount"
                                   step="0.01"
                                   min="0.01"
                                   required
                                   placeholder="0.00"
                                   class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono font-bold focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                        </div>

                        <!-- Occurred Date -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Occurred Date</label>
                            <input type="date"
                                   name="occurred_at"
                                   value="{{ now()->format('Y-m-d') }}"
                                   required
                                   class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                        </div>

                        <!-- Reference -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Invoice / Reference (Optional)</label>
                            <input type="text"
                                   name="reference"
                                   placeholder="e.g. INV-2026-901 or Bill Receipt #5"
                                   class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Notes / Business Purpose (Optional)</label>
                            <textarea name="notes"
                                      rows="2"
                                      placeholder="Expenditure justification..."
                                      class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"></textarea>
                        </div>

                        <!-- Submit Bar -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-200">
                            <button type="button" @click="recordModalOpen = false"
                                    class="px-4 py-2 text-xs font-semibold text-neutral-700 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-5 py-2 text-xs font-bold text-white bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] rounded-xl shadow-xs transition-colors">
                                Save Expense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reject Reason Modal -->
        <div x-show="rejectModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="rejectModalOpen = false" class="fixed inset-0 bg-neutral-950/40 backdrop-blur-xs transition-opacity" aria-hidden="true"></div>

                <div class="inline-block align-bottom bg-white border border-neutral-200 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <template x-if="activeExpense">
                        <form :action="`/admin/expenses/${activeExpense.id}/reject`" method="POST" class="p-6 space-y-4">
                            @csrf

                            <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                                <div>
                                    <h3 class="text-base font-bold text-neutral-900">Reject Expense</h3>
                                    <p class="text-xs text-neutral-500 mt-0.5" x-text="`ID: ${activeExpense.id} (₹${activeExpense.amount})`"></p>
                                </div>
                                <button type="button" @click="rejectModalOpen = false" class="text-neutral-400 hover:text-neutral-600">
                                    <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                                </button>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                    Rejection Reason <span class="text-red-600 font-bold">*</span>
                                </label>
                                <textarea name="rejection_reason"
                                          x-model="rejectionReason"
                                          rows="3"
                                          required
                                          placeholder="Explain why this expense is being rejected..."
                                          class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-200">
                                <button type="button" @click="rejectModalOpen = false"
                                        class="px-4 py-2 text-xs font-semibold text-neutral-700 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-xs transition-colors">
                                    Confirm Rejection
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>
