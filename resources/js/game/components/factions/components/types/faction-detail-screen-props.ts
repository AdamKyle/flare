import FactionDefinition from '../../api/definitions/faction-definition';

export default interface FactionDetailScreenProps {
  character_id: number;
  faction: FactionDefinition;
  on_faction_updated: (faction: FactionDefinition) => void;
  on_open_faction_loyalty: () => void;
}
