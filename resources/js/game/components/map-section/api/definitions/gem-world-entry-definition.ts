import AreaGemContextDefinition from '../../../../reusable-components/gems/api/definitions/area-gem-context-definition';

export default interface GemWorldEntryDefinition {
  type: 'map_gem' | 'location_gem';
  label: 'Enter Map Gem' | 'Enter Location Gem';
  generated_game_map: { id: number; name: string };
  context: AreaGemContextDefinition;
}
