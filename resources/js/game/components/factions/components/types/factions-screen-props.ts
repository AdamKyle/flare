import FactionDefinition from '../../api/definitions/faction-definition';

export default interface FactionsScreenProps {
  character_id: number;
  on_open_faction: (faction: FactionDefinition) => void;
}
