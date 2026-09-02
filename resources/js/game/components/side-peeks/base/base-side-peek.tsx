import clsx from 'clsx';
import { useEventSystem } from 'event-system/hooks/use-event-system';
import { AnimatePresence } from 'framer-motion';
import React, { useEffect } from 'react';

import { resolveSidePeekContentScrollMode } from './component-registration/side-peek-component-mapper';
import { SidePeekContentScrollMode } from './enums/side-peek-content-scroll-mode';
import { CloseSidePeekEventMap } from './event-map/side-peek-event-map';
import { SidePeek as SidePeekEventType } from './event-types/side-peek';
import { useDynamicComponentVisibility } from './hooks/use-manage-side-peek-visibility';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import SidePeek from 'ui/side-peek/side-peek';

const BaseSidePeek = () => {
  const eventSystem = useEventSystem();

  const { ComponentToRender, componentKey, componentProps, closeSidePeek } =
    useDynamicComponentVisibility();

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

  const handleSecondaryActionClick = () => {
    closeSidePeek();

    if (componentProps.footer_secondary_action) {
      componentProps.footer_secondary_action();
    }
  };

  const renderFooterSecondaryAction = () => {
    return (
      <Button
        on_click={handleSecondaryActionClick}
        label={componentProps.footer_secondary_label || 'Cancel'}
        variant={ButtonVariant.DANGER}
      />
    );
  };

  const renderFooterPrimaryAction = () => {
    if (!componentProps.footer_primary_action) {
      return;
    }

    return (
      <Button
        on_click={componentProps.footer_primary_action}
        label={componentProps.footer_primary_label || ''}
        variant={ButtonVariant.PRIMARY}
      />
    );
  };

  const renderFooter = () => {
    if (!componentProps.has_footer) {
      return null;
    }

    return (
      <div className="flex justify-between border-t p-4">
        {renderFooterSecondaryAction()}
        {renderFooterPrimaryAction()}
      </div>
    );
  };

  return (
    <AnimatePresence>
      {componentProps.is_open && (
        <SidePeek
          key="side-peek"
          title={componentProps.title}
          is_open={componentProps.is_open}
          on_close={closeSidePeek}
          allow_clicking_outside={componentProps.allow_clicking_outside}
        >
          <div className={outerWrapperClassName}>
            <div className={contentWrapperClassName}>
              {ComponentToRender && <ComponentToRender {...componentProps} />}
            </div>
            {renderFooter()}
          </div>
        </SidePeek>
      )}
    </AnimatePresence>
  );
};

export default BaseSidePeek;
