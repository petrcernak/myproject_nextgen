<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Amendment;
use App\Models\ChangeOrder;
use App\Models\Contract;
use App\Models\File;
use App\Models\FileTag;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\RetentionBankGuarantee;
use App\Models\RetentionRelease;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FileController extends Controller
{
    public function indexGlobal(Request $request): View|RedirectResponse
    {
        $projectId = session('current_project_id');
        if (!$projectId) {
            return redirect()->route('projects.index')->with('error', __('Please select a project first.'));
        }

        $contractIds            = Contract::where('project_id', $projectId)->pluck('id');
        $amendmentIds           = Amendment::whereIn('contract_id', $contractIds)->pluck('id');
        $changeOrderIds         = ChangeOrder::whereIn('contract_id', $contractIds)->pluck('id');
        $invoiceIds             = Invoice::whereIn('contract_id', $contractIds)->pluck('id');
        $retentionReleaseIds    = RetentionRelease::whereIn('contract_id', $contractIds)->pluck('id');
        $retentionGuaranteeIds  = RetentionBankGuarantee::whereIn('contract_id', $contractIds)->pluck('id');

        $typeMap = [
            'contract'             => [Contract::class,              $contractIds],
            'amendment'            => [Amendment::class,             $amendmentIds],
            'change_order'         => [ChangeOrder::class,           $changeOrderIds],
            'invoice'              => [Invoice::class,               $invoiceIds],
            'retention_release'    => [RetentionRelease::class,      $retentionReleaseIds],
            'retention_guarantee'  => [RetentionBankGuarantee::class,$retentionGuaranteeIds],
        ];

        $files = File::with(['tags', 'uploader',
                'fileable' => fn (MorphTo $m) => $m->morphWith([
                    Contract::class              => [],
                    Amendment::class             => ['contract'],
                    ChangeOrder::class           => ['contract'],
                    Invoice::class               => ['contract'],
                    RetentionRelease::class      => ['contract'],
                    RetentionBankGuarantee::class => ['contract'],
                ]),
            ])
            ->where(function ($q) use ($typeMap, $request) {
                $active = $request->type && isset($typeMap[$request->type])
                    ? [$request->type => $typeMap[$request->type]]
                    : $typeMap;
                $q->where(function ($inner) use ($active) {
                    foreach ($active as [$class, $ids]) {
                        $inner->orWhere(fn ($s) => $s->where('fileable_type', $class)->whereIn('fileable_id', $ids));
                    }
                });
            })
            ->when($request->tag,      fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('name', $request->tag)))
            ->when($request->search,   fn ($q) => $q->where('original_name', 'ilike', '%'.$request->search.'%'))
            ->when($request->date_from,fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,  fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        $totalCount = File::where(function ($q) use ($typeMap) {
            foreach ($typeMap as [$class, $ids]) {
                $q->orWhere(fn ($s) => $s->where('fileable_type', $class)->whereIn('fileable_id', $ids));
            }
        })->count();

        $allTags = FileTag::where('id_group', $this->currentGroupId())->orderBy('name')->pluck('name');

        return view('files.index', compact('files', 'allTags', 'totalCount'));
    }

    private function sanitizeFilename(string $name): string
    {
        $from = ['á','č','ď','é','ě','í','ň','ó','ř','š','ť','ú','ů','ý','ž',
                 'Á','Č','Ď','É','Ě','Í','Ň','Ó','Ř','Š','Ť','Ú','Ů','Ý','Ž'];
        $to   = ['a','c','d','e','e','i','n','o','r','s','t','u','u','y','z',
                 'A','C','D','E','E','I','N','O','R','S','T','U','U','Y','Z'];
        $name = str_replace($from, $to, $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $name);
        return $name;
    }

    private function resolveProject(Model $model): ?Project
    {
        return match(true) {
            $model instanceof Contract              => $model->project,
            $model instanceof Amendment             => $model->contract->project,
            $model instanceof ChangeOrder           => $model->contract->project,
            $model instanceof Invoice               => $model->contract->project,
            $model instanceof RetentionRelease      => $model->contract->project,
            $model instanceof RetentionBankGuarantee => $model->contract->project,
            default => null,
        };
    }

    private function authorizeModelFile(Model $model): void
    {
        $project = $this->resolveProject($model);
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
    }

    private function authorizeFile(File $file): void
    {
        $fileable = $file->fileable;
        abort_unless($fileable !== null, 404);
        $this->authorizeModelFile($fileable);
    }

    private function doStore(Request $request, Model $model, string $autoTag): RedirectResponse
    {
        $this->authorizeModelFile($model);

        $request->validate([
            'file' => ['required', 'file', 'max:204800'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);

        $project      = $this->resolveProject($model);
        $uploaded     = $request->file('file');
        $originalName = $uploaded->getClientOriginalName();
        $storedName   = time() . '_' . $this->sanitizeFilename($originalName);
        $path         = "groups/{$project->id_group}/projects/{$project->id}/{$storedName}";

        Storage::put($path, file_get_contents($uploaded->getRealPath()));

        $file = File::create([
            'fileable_type' => get_class($model),
            'fileable_id'   => $model->getKey(),
            'original_name' => $originalName,
            'stored_name'   => $storedName,
            'path'          => $path,
            'mime_type'     => $uploaded->getMimeType(),
            'size'          => $uploaded->getSize(),
            'uploaded_by'   => $this->currentUser()->id,
        ]);

        $tagNames = [$autoTag];
        if ($request->filled('tags')) {
            foreach (explode(',', $request->tags) as $t) {
                $t = mb_strtolower(trim($t));
                if ($t !== '' && !in_array($t, $tagNames)) {
                    $tagNames[] = $t;
                }
            }
        }
        $tagIds = [];
        foreach ($tagNames as $tagName) {
            $tag      = FileTag::firstOrCreate(['id_group' => $this->currentGroupId(), 'name' => $tagName]);
            $tagIds[] = $tag->id;
        }
        $file->tags()->sync($tagIds);
        ActivityLog::record('uploaded', $file);

        return back()->with('success', __('File uploaded.'));
    }

    public function storeForContract(Request $request, Contract $contract): RedirectResponse
    {
        return $this->doStore($request, $contract, 'contract');
    }

    public function storeForAmendment(Request $request, Amendment $amendment): RedirectResponse
    {
        return $this->doStore($request, $amendment, 'amendment');
    }

    public function storeForChangeOrder(Request $request, ChangeOrder $changeOrder): RedirectResponse
    {
        return $this->doStore($request, $changeOrder, 'change order');
    }

    public function storeForInvoice(Request $request, Invoice $invoice): RedirectResponse
    {
        return $this->doStore($request, $invoice, 'invoice');
    }

    public function storeForRetentionRelease(Request $request, RetentionRelease $retentionRelease): RedirectResponse
    {
        return $this->doStore($request, $retentionRelease, 'retention release');
    }

    public function storeForRetentionBankGuarantee(Request $request, RetentionBankGuarantee $retentionBankGuarantee): RedirectResponse
    {
        return $this->doStore($request, $retentionBankGuarantee, 'bank guarantee');
    }

    public function show(File $file): Response
    {
        $this->authorizeFile($file);

        abort_unless(Storage::exists($file->path), 404);

        return response(Storage::get($file->path), 200, [
            'Content-Type'        => $file->mime_type,
            'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    public function destroy(File $file): RedirectResponse
    {
        $this->authorizeFile($file);

        Storage::delete($file->path);
        $file->delete();

        return back()->with('success', __('File deleted.'));
    }
}
