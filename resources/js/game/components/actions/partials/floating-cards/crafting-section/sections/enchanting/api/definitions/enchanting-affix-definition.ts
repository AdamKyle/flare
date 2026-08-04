export default interface EnchantingAffixDefinition {
  id: number;
  name: string;
  type: 'prefix' | 'suffix';
  cost: number;
  int_required: number;
}
