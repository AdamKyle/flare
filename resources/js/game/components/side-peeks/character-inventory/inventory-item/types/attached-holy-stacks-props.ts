import { HolyStackDefinition } from '../../../../../api-definitions/items/equippable-item-definitions/holy-stack-definition';

export default interface AppliedHolyStacksSectionProps {
  stacks: HolyStackDefinition[];
  on_close: () => void;
}
