export default interface QueenAffixDefinition {
  id: number;
  name: string;
  type: 'prefix' | 'suffix';
  cost: number;
  randomly_generated: boolean;
}
