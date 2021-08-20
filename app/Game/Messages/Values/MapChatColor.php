<?php

namespace App\Game\Messages\Values;

class MapChatColor {

    CONST SURFACE   = '#ffffff';
    CONST LABYRINTH = '#ffad47';
    const DUNGEONS  = '#755c59';

    /**
     * MapChatColor constructor.
     *
     * @param string $mapName
     */
    public function __construct(string $mapName) {
        $this->mapName = $mapName;
    }

    /**
     * Gets the chat color.
     *
     * @return string
     */
    public function getColor(): string {
        switch($this->mapName) {
            case 'Labyrinth':
                return self::LABYRINTH;
            case 'Dungeons':
                return self::DUNGEONS;
            case 'Surface':
            default:
                return self::SURFACE;
        }
    }
}
