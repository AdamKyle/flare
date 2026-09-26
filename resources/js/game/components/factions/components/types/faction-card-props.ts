import FactionDefinition from '../../api/definitions/faction-definition';

export default interface FactionCardProps {
  faction: FactionDefinition;
  is_pledged: boolean;
  on_open: (factionId: number) => void;
}
