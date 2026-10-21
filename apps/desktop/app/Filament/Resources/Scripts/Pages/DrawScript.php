<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\Pages;

use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\ScriptResource;
use Lorekeeper\Apps\Desktop\Models\Script;
use Lorekeeper\Apps\Desktop\Scripts\Palette;
use Override;

final class DrawScript extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ScriptResource::class;

    protected string $view = 'filament.pages.draw-script';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return $this->script()->name;
    }

    #[Override]
    public function getSubheading(): ?string
    {
        return $this->script()->author === null ? null : "by {$this->script()->author}";
    }

    /** @return array<string, string> */
    #[Override]
    public function getBreadcrumbs(): array
    {
        return [ScriptResource::getUrl() => 'Scripts', 'Draw'];
    }

    /**
     * @param array<array-key, mixed> $drawing
     *
     * @return array{nodes: array<string, list<string>>, others: list<string>, ready: bool}
     */
    public function save(array $drawing): array
    {
        $script = $this->script();
        $script->update(['drawing' => Script::cleaned($drawing)]);
        $this->skipRender();

        return $script->review();
    }

    /** @return array<string, mixed> */
    public function editor(): array
    {
        $kinds = [];
        foreach (Palette::kinds() as $kind => $about) {
            $kinds[$kind] = [...$about, 'icon' => svg($about['icon'])->toHtml()];
        }

        return [
            'drawing' => $this->script()->drawing,
            'kinds' => $kinds,
            'groups' => Palette::GROUPS,
            'review' => $this->script()->review(),
        ];
    }

    private function script(): Script
    {
        $script = $this->getRecord();
        assert($script instanceof Script, description: 'the record of a script page is a script');

        return $script;
    }
}
