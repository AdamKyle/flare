import React from 'react';
import {render} from 'react-dom';
import InventoryBase from "../components/game/character/inventory/inventory-base";

function renderInventory(id) {
  const boons       = document.getElementById(id);
  const characterId = document.querySelector('#' + id).dataset.character;
  const userId      = document.querySelector('#' + id).dataset.user;


  render(
    <InventoryBase characterId={characterId} userId={userId} />,
    boons
  );
}

window.characterInventory = renderInventory;
