import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import DuplicateGemAbilityDefinition from '../../../../../../api-definitions/gems/duplicate-gem-ability-definition';
import { EquippableItemWithBase } from '../../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';

export default interface SetItemsResponseDefinition extends PaginatedApiResponseDefinition<
  EquippableItemWithBase[]
> {
  duplicate_gem_abilities: DuplicateGemAbilityDefinition[];
}
