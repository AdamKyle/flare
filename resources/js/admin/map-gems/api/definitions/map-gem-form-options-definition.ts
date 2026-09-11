import { GemType } from '../../../../game/reusable-components/gems/enums/gem-type';

export interface MapGemRelatedIdentityDefinition {
  id: number;
  name: string;
}

export default interface MapGemFormOptionsDefinition {
  game_maps: MapGemRelatedIdentityDefinition[];
  crafting_skills: MapGemRelatedIdentityDefinition[];
  gem_types: GemType[];
}
