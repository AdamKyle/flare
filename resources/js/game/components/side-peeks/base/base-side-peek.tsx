import clsx from 'clsx';
import { useEventSystem } from 'event-system/hooks/use-event-system';
import { AnimatePresence } from 'framer-motion';
import React, { useCallback, useEffect, useMemo, useState } from 'react';

import { resolveSidePeekContentScrollMode } from './component-registration/side-peek-component-mapper';
import { SidePeekContentScrollMode } from './enums/side-peek-content-scroll-mode';
import { CloseSidePeekEventMap } from './event-map/side-peek-event-map';
import { SidePeek as SidePeekEventType } from './event-types/side-peek';
import { useDynamicComponentVisibility } from './hooks/use-manage-side-peek-visibility';

import SidePeekOptionsFooter from 'ui/side-peek/options/components/side-peek-options-footer';
import SidePeekOptionsContext from 'ui/side-peek/options/side-peek-options-context';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';
import SidePeekOptionsContextDefinition from 'ui/side-peek/options/types/side-peek-options-context-definition';
import SidePeek from 'ui/side-peek/side-peek';

const BaseSidePeek = () => {
  const eventSystem = useEventSystem();

  const { ComponentToRender, componentKey, componentProps, closeSidePeek } =
    useDynamicComponentVisibility();

  const [sidePeekOptions, setSidePeekOptions] = useState<
    SidePeekOptionDefinition[]
  >([]);

  const contentScrollMode = componentKey
    ? resolveSidePeekContentScrollMode(componentKey)
    : SidePeekContentScrollMode.PARENT;

  const contentWrapperClassName =
    contentScrollMode === SidePeekContentScrollMode.COMPONENT
      ? 'relative min-h-0 flex-1 overflow-hidden'
      : 'relative flex-1 overflow-x-hidden overflow-y-auto';

  const outerWrapperClassName = clsx(
    'flex h-full min-h-0 flex-col',
    contentScrollMode === SidePeekContentScrollMode.COMPONENT ? 'pb-0' : 'pb-4'
  );

  useEffect(() => {
    const emitter =
      eventSystem.fetchOrCreateEventEmitter<CloseSidePeekEventMap>(
        SidePeekEventType.CLOSE_SIDE_PEEK
      );

    const handleCloseSidePeek = () => {
      closeSidePeek();
    };

    emitter.on(SidePeekEventType.CLOSE_SIDE_PEEK, handleCloseSidePeek);

    return () => {
      emitter.off(SidePeekEventType.CLOSE_SIDE_PEEK, handleCloseSidePeek);
    };
  }, [eventSystem, closeSidePeek]);

  useEffect(() => {
    setSidePeekOptions([]);
  }, [componentProps.is_open, componentKey]);

  const setOptions = useCallback((options: SidePeekOptionDefinition[]) => {
    setSidePeekOptions(options);
  }, []);

  const clearOptions = useCallback(() => {
    setSidePeekOptions([]);
  }, []);

  const optionsContextValue = useMemo<SidePeekOptionsContextDefinition>(
    () => ({ set_options: setOptions, clear_options: clearOptions }),
    [setOptions, clearOptions]
  );

  return (
    <AnimatePresence>
      {componentProps.is_open && (
        <SidePeek
          key="side-peek"
          title={componentProps.title}
          is_open={componentProps.is_open}
          on_close={closeSidePeek}
          allow_clicking_outside={componentProps.allow_clicking_outside}
          footer={
            sidePeekOptions.length > 0 ? (
              <SidePeekOptionsFooter options={sidePeekOptions} />
            ) : undefined
          }
        >
          <SidePeekOptionsContext.Provider value={optionsContextValue}>
            <div className={outerWrapperClassName}>
              <div className={contentWrapperClassName}>
                {ComponentToRender && <ComponentToRender {...componentProps} />}
              </div>
            </div>
          </SidePeekOptionsContext.Provider>
        </SidePeek>
      )}
    </AnimatePresence>
  );
};

export default BaseSidePeek;
