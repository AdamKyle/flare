import GemWorldSourceDefinition from '../api/definitions/gem-world-source-definition';

export default interface AreaGemSourceCardProps {
  source: GemWorldSourceDefinition;
  on_click: (source: GemWorldSourceDefinition) => void;
}
