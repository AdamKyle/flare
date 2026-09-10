import AreaGemContextDefinition from '../../../../reusable-components/gems/api/definitions/area-gem-context-definition';

export default interface UseOpenGemWorldSidePeekDefinition {
  openGemWorld: (
    characterId: number,
    context: AreaGemContextDefinition,
    canEnter: boolean
  ) => void;
}
