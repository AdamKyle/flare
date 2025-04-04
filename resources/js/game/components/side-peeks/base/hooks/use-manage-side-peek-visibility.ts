import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { SidePeek } from '../event-types/side-peek';
import FallBackSidePeekComponent from "../fall-back-side-peek-component";
import { ComponentForSidePeekPropsType } from './types/component-for-side-peek-props-type';
import { ComponentForSidePeekType } from './types/component-for-side-peek-type';
import defaultSidePeekProps from './types/default-side-peek-props';

export const useDynamicComponentVisibility = <T extends object>() => {
  const eventSystem = useEventSystem();

  const [ComponentToRender, setComponentToRender] = useState<ComponentForSidePeekType<T>>(
    () => FallBackSidePeekComponent
  );


  const [componentProps, setComponentProps] = useState<ComponentForSidePeekPropsType<T>>(
    defaultSidePeekProps as ComponentForSidePeekPropsType<T>
  );

  const emitter = eventSystem.fetchOrCreateEventEmitter<{
    [SidePeek.SIDE_PEEK]: [ComponentForSidePeekType<T>, ComponentForSidePeekPropsType<T>];
  }>(SidePeek.SIDE_PEEK);

  useEffect(() => {
    const handleVisibilityChange = (
      component: ComponentForSidePeekType<T>,
      props: ComponentForSidePeekPropsType<T>
    ) => {
      setComponentToRender(component);
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
    emitter.emit(SidePeek.SIDE_PEEK, ComponentToRender, updatedProps);
    componentProps.on_close();
  };

  return {
    ComponentToRender,
    componentProps,
    closeSidePeek,
  };
};
