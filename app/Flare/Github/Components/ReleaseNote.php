<?php

namespace App\Flare\Github\Components;

use App\Flare\Github\Services\Markdown;
use App\Flare\Models\ReleaseNote as ReleaseNoteModel;
use Illuminate\View\Component;
use Illuminate\View\View;

class ReleaseNote extends Component
{
    public ReleaseNoteModel $releaseNote;

    public function __construct(ReleaseNoteModel $releaseNote, private readonly Markdown $markdown)
    {
        $this->releaseNote = $releaseNote;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): string|View
    {
        $content = $this->markdown->convertToHtml($this->releaseNote->body);

        return view('components.release-note', [
            'release' => $this->releaseNote,
            'content' => $content,
        ]);
    }
}
