import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { SidePeek } from '../event-types/side-peek';
import defaultSidePeekProps from './types/default-side-peek-props';
import { ComponentForSidePeekPropsType } from './types/component-for-side-peek-props-type';
import UseManageSidePeekVisibilityDefinition from './deffinitions/use-manage-side-peek-visibility-definition';
import {SidePeekComponentMapper} from "../component-registration/side-peek-component-mapper";
import {SidePeekComponentRegistrationEnum} from "../component-registration/side-peek-component-registration-enum";

export const useDynamicComponentVisibility = <T extends object>(): UseManageSidePeekVisibilityDefinition<T> => {
  const eventSystem = useEventSystem();

  const [componentKey, setComponentKey] = useState<SidePeekComponentRegistrationEnum | null>(null);
  const [componentProps, setComponentProps] = useState<ComponentForSidePeekPropsType<T>>(
    defaultSidePeekProps as ComponentForSidePeekPropsType<T>
  );

  const emitter = eventSystem.fetchOrCreateEventEmitter<{
    [SidePeek.SIDE_PEEK]: [SidePeekComponentRegistrationEnum, ComponentForSidePeekPropsType<T>];
  }>(SidePeek.SIDE_PEEK);

  useEffect(() => {
    const handleVisibilityChange = (
      key: SidePeekComponentRegistrationEnum,
      props: ComponentForSidePeekPropsType<T>
    ) => {
      setComponentKey(key);
      setComponentProps({
        ...defaultSidePeekProps,
        ...props,
      });
    };

    emitter.on(SidePeek.SIDE_PEEK, handleVisibilityChange);

    return () => {
      emitter.off(SidePeek.SIDE_PEEK, handleVisibilityChange);
    };
  }, [emitter]);

  const closeSidePeek = () => {
    const updatedProps = {
      ...componentProps,
      is_open: false,
    };

    setComponentProps(updatedProps);
    componentProps.on_close?.();
  };

  const mapper = SidePeekComponentMapper<T>();

  const ComponentToRender = componentKey ? mapper[componentKey] : null;


  return {
    ComponentToRender,
    componentProps,
    closeSidePeek
  };
};