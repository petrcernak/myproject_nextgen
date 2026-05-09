<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\File;
use App\Models\FileTag;
use App\Models\RetentionRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RetentionReleaseController extends Controller
{
    public function store(Request $request, Contract $contract): RedirectResponse
    {
        abort_unless($contract->project && $contract->project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($contract->project), 403);

        $data = $request->validate([
            'type'         => ['required', 'in:short,long'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'release_date' => ['required', 'date'],
            'note'         => ['nullable', 'string', 'max:1000'],
            'file'         => ['nullable', 'file', 'max:204800'],
        ]);
        $data['contract_id'] = $contract->id;

        $release = RetentionRelease::create(\Arr::except($data, ['file']));

        if ($request->hasFile('file')) {
            $project      = $contract->project;
            $uploaded     = $request->file('file');
            $original     = $uploaded->getClientOriginalName();
            $stored       = time() . '_' . preg_replace('/\s+/', '_', preg_replace('/[^a-zA-Z0-9_.\-]/', '', $original));
            $path         = "groups/{$project->id_group}/projects/{$project->id}/{$stored}";
            Storage::put($path, file_get_contents($uploaded->getRealPath()));
            $file = File::create([
                'fileable_type' => RetentionRelease::class,
                'fileable_id'   => $release->id,
                'original_name' => $original,
                'stored_name'   => $stored,
                'path'          => $path,
                'mime_type'     => $uploaded->getMimeType(),
                'size'          => $uploaded->getSize(),
                'uploaded_by'   => $this->currentUser()->id,
            ]);
            $tag = FileTag::firstOrCreate(['id_group' => $this->currentGroupId(), 'name' => 'retention release']);
            $file->tags()->sync([$tag->id]);
            ActivityLog::record('uploaded', $file);
        }

        return back()->with('success', __('Retention release recorded.'));
    }

    public function destroy(RetentionRelease $retentionRelease): RedirectResponse
    {
        $contract = $retentionRelease->contract;
        abort_unless($contract->project && $contract->project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($contract->project), 403);

        $retentionRelease->files->each(function ($file) {
            Storage::delete($file->path);
            $file->delete();
        });
        $retentionRelease->delete();

        return back()->with('success', __('Release deleted.'));
    }
}
