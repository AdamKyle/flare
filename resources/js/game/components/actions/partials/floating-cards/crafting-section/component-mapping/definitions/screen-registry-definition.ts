import React from 'react';

import { CraftingTypes } from '../../enums/crafting-types';
import { PropsMapping } from '../props-mapping';

export default interface ScreenRegistryDefinition {
  screens: Partial<{
    [K in CraftingTypes]: React.ComponentType<PropsMapping[K]>;
  }>;
}
