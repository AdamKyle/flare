import React from 'react';
import {render} from 'react-dom';
import CharacterSheet from "../components/game/character/character-sheet";

function renderCharacterSheet(id) {
  const sheet       = document.getElementById(id);
  const characterId = document.querySelector('#' + id).dataset.character;
  const userId      = document.querySelector('#' + id).dataset.user;


  render(
    <CharacterSheet characterId={characterId} userId={userId} />,
    sheet
  );
}

window.characterSheet = renderCharacterSheet;
