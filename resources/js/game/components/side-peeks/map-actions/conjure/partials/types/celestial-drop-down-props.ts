import CelestialOptionDefinition from '../../api/definitions/celestial-option-definition';

export default interface CelestialDropDownProps {
  celestials: CelestialOptionDefinition[];
  on_select: (celestial: CelestialOptionDefinition) => void;
  on_clear: () => void;
  aria_labelled_by?: string;
}
