import clsx from 'clsx';
import { useScreenNavigation } from 'configuration/screen-manager/screen-manager-kit';
import { ScreenHost } from 'configuration/screen-manager/screen-manager-kit';
import { motion } from 'framer-motion';
import React from 'react';

import MobileNav from './components/actions/components/mobile-nav-bar/mobile-nav';
import Chat from './components/chat-section/chat';
import { GameCard } from './components/game-card';
import ScreenBindingHost from '../screen-manager/screen-binding-host';
import GameLoader from './components/game-loader/game-loader';
import { useGameLoaderVisibility } from './components/hooks/use-game-loader-visibility';
import { gameScreenBindings } from './screen-bindings';

const GameSection = () => {
  const { showGameLoader } = useGameLoaderVisibility();
  const { stackDepth } = useScreenNavigation();

  if (showGameLoader) {
    return <GameLoader />;
  }

  const renderShell = () => {
    return (
      <div className="mobile-shell">
        <ScreenBindingHost bindings={gameScreenBindings} />

        <div className="grid">
          <div className="row-start-1 col-start-1 z-10">
            <motion.div
              className={clsx({
                'pointer-events-none': stackDepth > 0,
              })}
              initial={false}
              animate={{ opacity: stackDepth > 0 ? 0 : 1 }}
              transition={{ duration: 0.25 }}
              aria-hidden={stackDepth > 0}
            >
              <GameCard />
            </motion.div>
          </div>

          <div className="row-start-1 col-start-1">
            <ScreenHost />
          </div>
        </div>

        <Chat />
        <MobileNav />
      </div>
    );
  };

  return renderShell();
};

export default GameSection;
