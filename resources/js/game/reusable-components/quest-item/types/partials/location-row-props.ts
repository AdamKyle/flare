import { LocationIdentityDefinition } from '../quest-item-factual-definition';

export default interface LocationRowProps {
  heading: string;
  location: LocationIdentityDefinition;
  on_open_location?: (id: number) => void;
  on_open_map?: (id: number) => void;
}
