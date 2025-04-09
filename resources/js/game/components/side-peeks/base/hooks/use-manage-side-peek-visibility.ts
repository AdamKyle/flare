import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { SidePeek } from '../event-types/side-peek';
import UseManageSidePeekVisibilityDefinition, {
  AllSidePeekProps
} from './deffinitions/use-manage-side-peek-visibility-definition';
import defaultSidePeekProps from './types/default-side-peek-props';
import { SidePeekComponentMapper } from '../component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../component-registration/side-peek-component-registration-enum';
import { SidePeekEventMap } from '../event-map/side-peek-event-map';

export const useDynamicComponentVisibility = (): UseManageSidePeekVisibilityDefinition => {
  const eventSystem = useEventSystem();

  const [componentKey, setComponentKey] =
    useState<SidePeekComponentRegistrationEnum | null>(null);

  const [componentProps, setComponentProps] = useState<AllSidePeekProps>(
    defaultSidePeekProps as AllSidePeekProps
  );

  const emitter = eventSystem.fetchOrCreateEventEmitter<SidePeekEventMap>(SidePeek.SIDE_PEEK);

  useEffect(() => {
    const handleVisibilityChange = (
      key: SidePeekComponentRegistrationEnum,
      props: AllSidePeekProps
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

  const ComponentToRender = componentKey ? SidePeekComponentMapper[componentKey] : null;

  return {
    ComponentToRender,
    componentProps,
    closeSidePeek,
  };
};
