import React from 'react';

import { SidePeekComponentRegistrationEnum } from './side-peek-component-registration-enum';
import BackPack from '../../character-inventory/backpack';
import { ComponentForSidePeekPropsType } from '../hooks/types/component-for-side-peek-props-type';

export const SidePeekComponentMapper = <T extends object>() => {
  return {
    [SidePeekComponentRegistrationEnum.BACKPACK]: BackPack,
  } as Record<
    SidePeekComponentRegistrationEnum,
    React.ComponentType<ComponentForSidePeekPropsType<T>>
  >;
};
