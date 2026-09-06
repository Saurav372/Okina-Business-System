<x-layouts.admin title="Operational Expenses" description="Track business expenditure, category allocations, proof attachments, and approval workflows.">
    <x-slot:header>
        @can('viewExpenseReports', \App\Models\Expense::class)
            <a href="{{ route('admin.expenses.export', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-neutral-300 text-neutral-700 rounded-xl hover:bg-neutral-50 transition-colors shadow-xs">
                <x-icons.lucide name="lucide-download" class="w-4 h-4 text-neutral-500" />
                <span>Export CSV</span>
            </a>
        @endcan

        @can('create', \App\Models\ExpenseCategory::class)
            <button type="button"
                    @click="$dispatch('open-category-modal')"
                    onclick="window.dispatchEvent(new CustomEvent('open-category-modal'))"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-neutral-100 text-neutral-700 rounded-xl hover:bg-neutral-200 transition-colors shadow-xs">
                <x-icons.lucide name="lucide-folder-tree" class="w-4 h-4 text-neutral-600" />
                <span>Categories</span>
            </button>
        @endcan

        @can('create', \App\Models\Expense::class)
            <button type="button"
                    @click="$dispatch('open-overlay', 'record-expense-modal')"
                    aria-haspopup="dialog"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors shadow-xs">
                <x-icons.lucide name="lucide-plus" class="w-4 h-4" />
                <span>Record Expense</span>
            </button>
        @endcan
    </x-slot:header>

    <div class="space-y-6" 
         @open-category-modal.window="categoryModalOpen = true"
         x-data="{
             categoryModalOpen: {{ ($errors->category->any() || request('open_categories') == 1) ? 'true' : 'false' }},
             rejectModalOpen: {{ $errors->rejection->any() ? 'true' : 'false' }},
             activeExpense: null,
             rejectionReason: '',

             openRejectModal(expense) {
                 this.activeExpense = expense;
                 this.rejectionReason = '';
                 this.rejectModalOpen = true;
             }
         }">

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
        <x-modal
            id="record-expense-modal"
            title="Record operational expense"
            size="2xl"
            initial-focus="expense_category_public_id"
        >
            <form
                id="record-expense-form"
                method="POST"
                action="{{ route('admin.expenses.store') }}"
                enctype="multipart/form-data"
                class="space-y-5"
                novalidate
            >
                @csrf
                <input type="hidden" name="expense_modal_mode" value="create">

                <div>
                    <p class="text-sm font-semibold text-neutral-900">Expense details</p>
                    <p class="mt-1 text-xs leading-5 text-neutral-500">Record the amount, date, and supporting information. New expenses are saved as drafts.</p>
                </div>

                @if ($errors->expense->any())
                    <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-900">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                            </svg>
                            <div>
                                <p class="text-xs font-bold">Check the highlighted fields</p>
                                <ul class="mt-1 list-disc space-y-0.5 pl-4 text-xs text-rose-800">
                                    @foreach ($errors->expense->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <div>
                    <label for="expense_category_public_id" class="mb-1.5 block text-xs font-semibold text-neutral-700">
                        Expense category <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only"> (required)</span>
                    </label>
                    <select
                        id="expense_category_public_id"
                        name="expense_category_public_id"
                        required
                        aria-invalid="{{ $errors->expense->has('expense_category_public_id') ? 'true' : 'false' }}"
                        aria-describedby="expense-category-help{{ $errors->expense->has('expense_category_public_id') ? ' expense-category-error' : '' }}"
                        @class([
                            'min-h-11 w-full rounded-xl border bg-white px-3.5 py-2.5 text-base text-neutral-900 transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] sm:text-sm',
                            'border-rose-400' => $errors->expense->has('expense_category_public_id'),
                            'border-neutral-300' => ! $errors->expense->has('expense_category_public_id'),
                        ])
                    >
                        <option value="">Select a category</option>
                        @foreach ($categories->where('is_active', true) as $cat)
                            <option value="{{ $cat->public_id }}" @selected(old('expense_category_public_id') === $cat->public_id)>{{ $cat->name }} ({{ $cat->code }})</option>
                        @endforeach
                    </select>
                    <p id="expense-category-help" class="mt-1.5 text-[11px] text-neutral-500">Only active categories are available.</p>
                    @error('expense_category_public_id', 'expense')
                        <p id="expense-category-error" class="mt-1.5 text-xs font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="expense_amount" class="mb-1.5 block text-xs font-semibold text-neutral-700">
                            Amount <span class="font-normal text-neutral-400">(INR)</span> <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only"> (required)</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-neutral-500" aria-hidden="true">₹</span>
                            <input
                                id="expense_amount"
                                type="text"
                                name="amount"
                                value="{{ old('amount') }}"
                                inputmode="decimal"
                                autocomplete="off"
                                placeholder="250.50"
                                required
                                aria-invalid="{{ $errors->expense->has('amount') ? 'true' : 'false' }}"
                                aria-describedby="expense-amount-help{{ $errors->expense->has('amount') ? ' expense-amount-error' : '' }}"
                                @class([
                                    'min-h-11 w-full rounded-xl border bg-white py-2.5 pl-8 pr-3.5 text-base text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] sm:text-sm',
                                    'border-rose-400' => $errors->expense->has('amount'),
                                    'border-neutral-300' => ! $errors->expense->has('amount'),
                                ])
                            >
                        </div>
                        <p id="expense-amount-help" class="mt-1.5 text-[11px] text-neutral-500">Enter rupees and paise, for example 250.50.</p>
                        @error('amount', 'expense')
                            <p id="expense-amount-error" class="mt-1.5 text-xs font-medium text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="expense_occurred_at" class="mb-1.5 block text-xs font-semibold text-neutral-700">
                            Expense date <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only"> (required)</span>
                        </label>
                        <input
                            id="expense_occurred_at"
                            type="date"
                            name="occurred_at"
                            value="{{ old('occurred_at', now()->toDateString()) }}"
                            max="{{ now()->toDateString() }}"
                            required
                            aria-invalid="{{ $errors->expense->has('occurred_at') ? 'true' : 'false' }}"
                            aria-describedby="expense-date-help{{ $errors->expense->has('occurred_at') ? ' expense-date-error' : '' }}"
                            @class([
                                'min-h-11 w-full rounded-xl border bg-white px-3.5 py-2.5 text-base text-neutral-900 transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] sm:text-sm',
                                'border-rose-400' => $errors->expense->has('occurred_at'),
                                'border-neutral-300' => ! $errors->expense->has('occurred_at'),
                            ])
                        >
                        <p id="expense-date-help" class="mt-1.5 text-[11px] text-neutral-500">The date cannot be in the future.</p>
                        @error('occurred_at', 'expense')
                            <p id="expense-date-error" class="mt-1.5 text-xs font-medium text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="expense_reference" class="mb-1.5 block text-xs font-semibold text-neutral-700">Reference number <span class="font-normal text-neutral-400">(optional)</span></label>
                    <input
                        id="expense_reference"
                        type="text"
                        name="reference"
                        value="{{ old('reference') }}"
                        autocomplete="off"
                        placeholder="Invoice, receipt, or voucher number"
                        aria-invalid="{{ $errors->expense->has('reference') ? 'true' : 'false' }}"
                        @class([
                            'min-h-11 w-full rounded-xl border bg-white px-3.5 py-2.5 text-base text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] sm:text-sm',
                            'border-rose-400' => $errors->expense->has('reference'),
                            'border-neutral-300' => ! $errors->expense->has('reference'),
                        ])
                    >
                    @error('reference', 'expense')
                        <p class="mt-1.5 text-xs font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="expense_notes" class="mb-1.5 block text-xs font-semibold text-neutral-700">Notes or description <span class="font-normal text-neutral-400">(optional)</span></label>
                    <textarea
                        id="expense_notes"
                        name="notes"
                        rows="3"
                        placeholder="What was this expense for?"
                        aria-invalid="{{ $errors->expense->has('notes') ? 'true' : 'false' }}"
                        @class([
                            'w-full resize-y rounded-xl border bg-white px-3.5 py-2.5 text-base text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] sm:text-sm',
                            'border-rose-400' => $errors->expense->has('notes'),
                            'border-neutral-300' => ! $errors->expense->has('notes'),
                        ])
                    >{{ old('notes') }}</textarea>
                    @error('notes', 'expense')
                        <p class="mt-1.5 text-xs font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="expense_proof_file" class="mb-1.5 block text-xs font-semibold text-neutral-700">Proof attachment <span class="font-normal text-neutral-400">(optional)</span></label>
                    <input
                        id="expense_proof_file"
                        type="file"
                        name="proof_file"
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                        aria-describedby="expense-proof-help{{ $errors->expense->has('proof_file') ? ' expense-proof-error' : '' }}"
                        aria-invalid="{{ $errors->expense->has('proof_file') ? 'true' : 'false' }}"
                        @class([
                            'min-h-11 w-full cursor-pointer rounded-xl border bg-white text-sm text-neutral-600 file:mr-3 file:min-h-11 file:border-0 file:border-r file:border-neutral-200 file:bg-neutral-50 file:px-4 file:text-xs file:font-semibold file:text-neutral-700 hover:file:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]',
                            'border-rose-400' => $errors->expense->has('proof_file'),
                            'border-neutral-300' => ! $errors->expense->has('proof_file'),
                        ])
                    >
                    <p id="expense-proof-help" class="mt-1.5 text-[11px] text-neutral-500">PDF, JPG, PNG, or WebP up to 10 MB.</p>
                    @error('proof_file', 'expense')
                        <p id="expense-proof-error" class="mt-1.5 text-xs font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
            </form>

            <x-slot:footer>
                <button
                    type="button"
                    @click="closeModal()"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-xs font-semibold text-neutral-700 transition-colors hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    form="record-expense-form"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[color:var(--color-brand-600)] px-5 py-2.5 text-xs font-bold text-white shadow-xs transition-colors hover:bg-[color:var(--color-brand-700)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] focus-visible:ring-offset-2"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m5 12 4 4L19 6" />
                    </svg>
                    Save expense
                </button>
            </x-slot:footer>
        </x-modal>

        @if ($errors->expense->any())
            <div x-init="$nextTick(() => $dispatch('open-overlay', 'record-expense-modal'))"></div>
        @endif

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
                <form 
                    method="POST" 
                    action="{{ route('admin.expense_categories.store') }}" 
                    x-data="{ 
                        catName: '', 
                        catCode: '', 
                        autoCode: true,
                        onNameChange() {
                            if (this.autoCode) {
                                this.catCode = this.catName.trim().toUpperCase().replace(/[^A-Z0-9]+/g, '_').slice(0, 30);
                            }
                        }
                    }"
                    class="mb-6 space-y-3 bg-neutral-50 p-4 rounded-xl border border-neutral-200"
                >
                    @csrf
                    <div class="text-xs font-bold text-neutral-800 uppercase">Create New Category</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <input 
                                type="text" 
                                name="name" 
                                x-model="catName"
                                @input="onNameChange()"
                                placeholder="Category Name (e.g. Utilities) *" 
                                required 
                                class="w-full text-xs rounded-xl border-neutral-300" 
                            />
                        </div>
                        <div>
                            <input 
                                type="text" 
                                name="code" 
                                x-model="catCode"
                                @input="autoCode = false"
                                placeholder="Machine Code e.g. UTILITIES *" 
                                required 
                                class="w-full text-xs rounded-xl border-neutral-300 uppercase font-mono" 
                            />
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
