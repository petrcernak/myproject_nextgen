<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\RetentionBankGuarantee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RetentionBankGuaranteeController extends Controller
{
    public function store(Request $request, Contract $contract): RedirectResponse
    {
        abort_unless($contract->project && $contract->project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($contract->project), 403);

        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'valid_from'  => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'note'        => ['nullable', 'string', 'max:1000'],
        ]);
        $data['contract_id'] = $contract->id;

        RetentionBankGuarantee::create($data);

        return back()->with('success', __('Bank guarantee recorded.'));
    }

    public function destroy(RetentionBankGuarantee $retentionBankGuarantee): RedirectResponse
    {
        $contract = $retentionBankGuarantee->contract;
        abort_unless($contract->project && $contract->project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($contract->project), 403);

        $retentionBankGuarantee->files->each(function ($file) {
            Storage::delete($file->path);
            $file->delete();
        });
        $retentionBankGuarantee->delete();

        return back()->with('success', __('Bank guarantee deleted.'));
    }
}
