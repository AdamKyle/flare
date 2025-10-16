import React from 'react';

import MobileNav from './components/actions/components/mobile-nav-bar/mobile-nav';
import Chat from './components/chat-section/chat';
import { GameCard } from './components/game-card';
import GameLoader from './components/game-loader/game-loader';
import { useGameLoaderVisibility } from './components/hooks/use-game-loader-visibility';

const GameSection = () => {
  const { showGameLoader } = useGameLoaderVisibility();

  if (showGameLoader) {
    return <GameLoader />;
  }

  return (
    <div className="mobile-shell">
      <GameCard />
      <Chat />
      <MobileNav />
    </div>
  );
};

export default GameSection;
