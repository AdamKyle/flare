import React from 'react';
import {render} from 'react-dom';
import KingdomUnitMovement from '../components/game/kingdom/kingdom-unit-movement';


function renderMovement(id) {
  const kingdomUnitMovement = document.getElementById(id);
  const characterId = document.querySelector('#' + id).dataset.character;
  const userId      = document.querySelector('#' + id).dataset.user;


  render(
    <KingdomUnitMovement characterId={characterId} userId={userId} />,
    kingdomUnitMovement
  );
}

window.renderKingdomMovement = renderMovement;
