import ExplorationOutputResponseDefinition from '../../../game/components/actions/partials/monster-section/exploration/types/exploration-output-response-definition';

export default interface ExplorationOutputUpdatesWireProps {
  userId: number;
  onEvent: (data: ExplorationOutputResponseDefinition) => void;
}
