<?php

namespace App\Livewire\RecurringExpenses;

use App\Livewire\Concerns\Sortable;
use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Services\ExpenseGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class IndexRecurringExpenses extends Component
{
    use Sortable;
    use Toast;
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';
    #[Url(except: '')]
    public string $activeFilter = '';

    // --- Popup konfirmasi occurrence pending yang sudah jatuh waktu ---
    public bool $showConfirm = false;
    /** Nominal aktual per occurrence (keyed by xpid), default = nominal template. */
    public array $confirmAmounts = [];
    /** Tanggal tunda per occurrence (keyed by xpid). */
    public array $snoozeDates = [];

    public function mount(ExpenseGenerationService $gen): void
    {
        // Lazy roll-forward: buat occurrence sampai hari ini saat halaman dibuka.
        $gen->ensureAllActive();

        $due = $this->duePending()->get();
        if ($due->isNotEmpty()) {
            $this->showConfirm = true;
            foreach ($due as $e) {
                $this->confirmAmounts[$e->xpid] = (int) $e->amount;
                $this->snoozeDates[$e->xpid] = Carbon::tomorrow()->toDateString();
            }
        }
    }

    /** Occurrence pending (draft) dari template rutin yang sudah jatuh waktunya. */
    protected function duePending()
    {
        return Expense::query()
            ->where('status', 'pending')
            ->where('is_voided', false)
            ->whereNotNull('rxid')
            ->whereDate('expense_date', '<=', Carbon::today())
            ->with('category')
            ->orderBy('expense_date')
            ->orderBy('xpid');
    }

    /** Konfirmasi occurrence: set nominal aktual → posted (masuk hitungan). */
    public function confirmPending(int $xpid): void
    {
        $e = Expense::where('status', 'pending')->where('is_voided', false)->find($xpid);
        if (! $e) {
            $this->refreshDue();
            return;
        }

        $amount = (int) ($this->confirmAmounts[$xpid] ?? $e->amount);
        if ($amount < 1) {
            $this->addError("confirmAmounts.$xpid", 'Nominal minimal Rp 1.');
            return;
        }

        $e->update([
            'amount' => $amount,
            'status' => 'posted',
            'modified_by' => Auth::id(),
        ]);

        unset($this->confirmAmounts[$xpid], $this->snoozeDates[$xpid]);
        $this->success('Pengeluaran dikonfirmasi & dicatat.');
        $this->refreshDue();
    }

    /** Tunda occurrence: pindahkan expense_date ke tanggal pilihan (tetap pending). */
    public function snoozePending(int $xpid): void
    {
        $e = Expense::where('status', 'pending')->where('is_voided', false)->find($xpid);
        if (! $e) {
            $this->refreshDue();
            return;
        }

        $date = $this->snoozeDates[$xpid] ?? null;
        if (! $date || Carbon::parse($date)->lte(Carbon::today())) {
            $this->addError("snoozeDates.$xpid", 'Pilih tanggal setelah hari ini.');
            return;
        }

        $e->update([
            'expense_date' => $date,
            'modified_by' => Auth::id(),
        ]);

        unset($this->confirmAmounts[$xpid], $this->snoozeDates[$xpid]);
        $this->success('Pengeluaran ditunda ke ' . Carbon::parse($date)->format('d-m-Y') . '.');
        $this->refreshDue();
    }

    /** Batalkan occurrence: hapus draft (template tetap aktif, generated_until tak berubah). */
    public function cancelPending(int $xpid): void
    {
        Expense::where('status', 'pending')
            ->where('is_voided', false)
            ->where('xpid', $xpid)
            ->delete();

        unset($this->confirmAmounts[$xpid], $this->snoozeDates[$xpid]);
        $this->success('Occurrence dibatalkan.');
        $this->refreshDue();
    }

    /** Tutup popup bila tidak ada lagi yang jatuh waktu. */
    protected function refreshDue(): void
    {
        if ($this->duePending()->count() === 0) {
            $this->showConfirm = false;
        }
    }

    /** Aktif/nonaktifkan template rutin. */
    public function toggleActive(int $rxid): void
    {
        $r = RecurringExpense::find($rxid);
        if (! $r) {
            return;
        }
        $r->update(['is_active' => ! $r->is_active, 'modified_by' => Auth::id()]);
        $this->success($r->is_active ? 'Template diaktifkan.' : 'Template dinonaktifkan.');
    }

    public function updated($prop): void
    {
        if (in_array($prop, ['search', 'activeFilter'], true)) {
            $this->resetPage();
        }
    }

    /** Kolom sortable (klik header). */
    protected function sortColumns(): array
    {
        return [
            'title' => 'title',
            'category' => '(SELECT ec.name FROM expense_categories ec WHERE ec.ecid = recurring_expenses.ecid)',
            'frequency' => 'frequency',
            'amount' => 'amount',
            'auto_post' => 'auto_post',
            'start_date' => 'start_date',
            'is_active' => 'is_active',
        ];
    }
    public function render()
    {
        $templates = RecurringExpense::query()
            ->with('category')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->activeFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->activeFilter === 'inactive', fn ($q) => $q->where('is_active', false))
;

        $this->applySort($templates, fn ($q) => $q->orderByDesc('is_active')->orderBy('title'));

        $templates = $templates->paginate(10);

        $duePending = $this->showConfirm ? $this->duePending()->get() : collect();

        return view('livewire.recurring-expenses.index', [
            'templates' => $templates,
            'duePending' => $duePending,
        ]);
    }
}
