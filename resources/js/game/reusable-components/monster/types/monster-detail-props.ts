import MonsterDetailDefinition, {
  MonsterGemEffectContextDefinition,
} from '../api/definitions/monster-detail-definition';

export interface MonsterNavigationDefinition {
  on_open_map?: (id: number) => void;
  on_open_item?: (id: number) => void;
  on_open_map_gem?: (profile_id: number) => void;
  on_open_location_gem?: (profile_id: number) => void;
}

export interface MonsterGemEffectContextBrowserProps {
  context_rows: MonsterGemEffectContextDefinition[];
  loading: boolean;
  loading_more: boolean;
  error: string | null;
  has_more: boolean;
  on_load_next: () => void;
  on_open_context?: (context: MonsterGemEffectContextDefinition) => void;
}

export default interface MonsterDetailProps {
  monster: MonsterDetailDefinition;
  navigation?: MonsterNavigationDefinition;
  initial_context_tab?: boolean;
  presentation?: 'page' | 'side-peek';
  gem_effect_context_browser?: MonsterGemEffectContextBrowserProps;
}
