<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractBudgetLink extends Model
{
    protected $fillable = ['contract_id', 'budget_id', 'fx_rate'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function linkedItemsCount(): int
    {
        return $this->contract->items()
            ->whereHas('budgetItem', fn ($q) => $q->whereHas('category.budget', fn ($q2) =>
                $q2->where('id', $this->budget_id)
            ))
            ->count();
    }

    public function isFullyLinked(): bool
    {
        $total = $this->contract->items()->count();
        return $total > 0 && $this->linkedItemsCount() >= $total;
    }
}
