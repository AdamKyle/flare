import React from 'react';
import {render} from 'react-dom';
import CharacterBoons from '../components/game/character/boons';


function renderBoons(id) {
  const boons       = document.getElementById(id);
  const characterId = document.querySelector('#' + id).dataset.character;
  const userId      = document.querySelector('#' + id).dataset.user;


  render(
    <CharacterBoons characterId={characterId} userId={userId} />,
    boons
  );
}

window.characterBoons = renderBoons;
