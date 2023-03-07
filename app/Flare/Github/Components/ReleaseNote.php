<?php

namespace App\Flare\Github\Components;


use Illuminate\View\Component;
use Illuminate\View\View;
use App\Flare\Github\Services\Markdown;
use App\Flare\Models\ReleaseNote as ReleaseNoteModel;

class ReleaseNote extends Component {

    /**
     * @var ReleaseNoteModel $releaseNote
     */
    public ReleaseNoteModel $releaseNote;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(ReleaseNoteModel $releaseNote) {
        $this->releaseNote = $releaseNote;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return string|View
     */
    public function render(): string|View{
        $content = resolve(Markdown::class)->convertToHtml($this->releaseNote->body);

        return view('components.release-note', [
            'release' => $this->releaseNote,
            'content' => $content
        ]);
    }
}
