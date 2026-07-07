<?php

namespace App\Models\Concerns;

use App\Models\Market;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Multi-tenancy per market. Dipasang di setiap model domain agar:
 *  1. SEMUA query Eloquent otomatis difilter `WHERE <tabel>.market_id = <market user login>`
 *     (mustahil lupa filter → tak ada kebocoran data antar market);
 *  2. `market_id` otomatis terisi saat create bila belum diset eksplisit.
 *
 * Bila tidak ada user login / user tanpa market (developer/superadmin/console),
 * scope menjadi inert (tanpa filter) — mis. command generator lintas market.
 */
trait BelongsToMarket
{
    protected static function bootBelongsToMarket(): void
    {
        static::addGlobalScope('market', function (Builder $builder) {
            $marketId = Auth::user()?->market_id;

            if ($marketId) {
                $model = $builder->getModel();
                $builder->where($model->getTable() . '.' . 'market_id', $marketId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->market_id) && ($marketId = Auth::user()?->market_id)) {
                $model->market_id = $marketId;
            }
        });
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'market_id', 'mid');
    }
}
