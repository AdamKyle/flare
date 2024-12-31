interface Atonements {
  fire: number;
  ice: number;
  water: number;
}

interface HighestElement {
  name: string;
  damage: number;
}

export default interface ElementalAtonementDefinition {
  atonements: Atonements;
  highest_element: HighestElement;
}
