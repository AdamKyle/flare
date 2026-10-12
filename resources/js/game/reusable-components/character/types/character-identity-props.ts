export default interface CharacterIdentityProps {
  race_id: number;
  race_name: string;
  class_id: number;
  class_name: string;
  on_open_race: (raceId: number, raceName: string) => void;
  on_open_class: (classId: number, className: string) => void;
  inline?: boolean;
}
