import { ExplorationPhase } from '../enums/exploration-phase';

export const resolveExplorationPhaseStatus = (
  phase: ExplorationPhase | null,
  currentRoundCreatures: number
): string => {
  switch (phase) {
    case ExplorationPhase.WAITING:
      return 'Waiting for the first encounter…';
    case ExplorationPhase.FIGHTING:
      return currentRoundCreatures > 0
        ? `Fighting ${currentRoundCreatures} enemies…`
        : 'Preparing a fight…';
    case ExplorationPhase.PROCESSING_REWARDS:
      return 'Processing rewards…';
    case ExplorationPhase.WAITING_FOR_NEXT_ENCOUNTER:
      return 'Waiting for the next encounter…';
    default:
      return 'Exploration is running.';
  }
};
