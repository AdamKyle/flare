import { createRoot, Root } from "react-dom/client";
import React from "react";
import CharacterTops from "./character-tops";

const characterTopsElement: HTMLElement | null =
    document.getElementById("character-tops");

if (characterTopsElement !== null) {
    const root: Root = createRoot(characterTopsElement);

    const selectedCharacterIdFromData =
        characterTopsElement.dataset.selectedCharacterId;
    const parsedSelectedCharacterId =
        typeof selectedCharacterIdFromData === "undefined"
            ? NaN
            : parseInt(selectedCharacterIdFromData);
    const selectedCharacterId = Number.isNaN(parsedSelectedCharacterId)
        ? null
        : parsedSelectedCharacterId;
    const props = {
        defaultPeriod:
            characterTopsElement.dataset.defaultPeriod ?? "current_month",
        selectedCharacterId: selectedCharacterId,
    };

    root.render(
        <CharacterTops
            default_period={props.defaultPeriod}
            selected_character_id={props.selectedCharacterId}
        />,
    );
}
