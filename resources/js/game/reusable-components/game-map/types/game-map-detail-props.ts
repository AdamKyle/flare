import { ReactNode } from 'react';

import GameMapFactualDefinition from './game-map-factual-definition';

export default interface GameMapDetailProps {
  game_map: GameMapFactualDefinition;
  split_layout?: boolean;
  single_column?: boolean;
  gem_section?: ReactNode;
}
