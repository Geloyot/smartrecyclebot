<?php

namespace App\Livewire;

use App\Models\ArmAction;
use Livewire\Component;
use Livewire\WithPagination;

class ArmActions extends Component
{
    use WithPagination;

    public $perPage    = 50;
    public $filterStatus = '';

    public function updatedFilterStatus() { $this->resetPage(); }
    public function updatedPerPage()      { $this->resetPage(); }

    public function render()
    {
        $query = ArmAction::with('wasteObject')->latest('performed_at');

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $logs = $query->paginate($this->perPage);

        $total   = ArmAction::count();
        $success = ArmAction::where('status', 'SUCCESS')->count();
        $warning = ArmAction::where('status', 'WARNING')->count();

        return view('livewire.arm-actions', compact('logs', 'total', 'success', 'warning'));
    }
}
