<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;

class BlockPolicy
{
    public function update(User $user, Block $block): bool
    {
        $siteId = $user->site?->id;

        return $siteId !== null && (int) $siteId === (int) $block->site_id;
    }

    public function delete(User $user, Block $block): bool
    {
        return $this->update($user, $block);
    }
}
