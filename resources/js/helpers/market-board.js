import React from 'react';
import {render} from 'react-dom';
import Board from '../components/marketboard/board';

/**
 * Helper to render the market board.
 * 
 * @param {Number} id 
 */
function renderBoard(id) {
    const marketBoard = document.getElementById(id);
    const character   = document.head.querySelector('meta[name="character"]');
    const itemId      = document.querySelector('#'+id).dataset.itemId;
    

    render(
        <Board characterId={character.content} itemId={parseInt(itemId)} />,
        marketBoard
    );
}

window.renderBoard = renderBoard;