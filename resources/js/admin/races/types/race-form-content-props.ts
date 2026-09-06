import RaceDefinition from '../api/definitions/race-definition';

export default interface RaceFormContentProps {
  race_id: number | null;
  on_saved: (race: RaceDefinition) => void;
  on_cancel: () => void;
  embedded: boolean;
}
