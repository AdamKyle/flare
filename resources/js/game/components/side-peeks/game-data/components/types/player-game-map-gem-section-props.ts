import AreaGemContextDefinition from '../../../../../reusable-components/gems/api/definitions/area-gem-context-definition';

export default interface PlayerGameMapGemSectionProps {
  gem_context: AreaGemContextDefinition;
  on_view_gem_profile: () => void;
  on_view_gem_effects: () => void;
}
