<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Lorekeeper\Apps\Desktop\Models\Account;

final class CharacterSheet extends Component
{
    public Account $account;

    public function render(): View
    {
        return view('livewire.character-sheet', [
            'scene' => $this->account->scene(),
            'playing' => $this->account->activeRun() !== null,
            'now' => now()->getTimestamp(),
        ]);
    }
}
