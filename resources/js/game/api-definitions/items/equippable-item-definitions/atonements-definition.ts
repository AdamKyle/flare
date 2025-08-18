export interface AtonementsDefinition {
  atonements: Record<string, number>;
  elemental_damage: {
    name: string;
    amount: number;
  };
}
