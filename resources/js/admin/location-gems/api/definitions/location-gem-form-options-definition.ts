import { GemType } from '../../../shared/enums/gem-type';

export interface LocationGemFormOptionLocationDefinition {
  id: number;
  name: string;
  type: number | null;
  map: { id: number; name: string } | null;
}

export interface LocationGemFormOptionSkillDefinition {
  id: number;
  name: string;
}

export default interface LocationGemFormOptionsDefinition {
  locations: LocationGemFormOptionLocationDefinition[];
  crafting_skills: LocationGemFormOptionSkillDefinition[];
  gem_types: GemType[];
}
