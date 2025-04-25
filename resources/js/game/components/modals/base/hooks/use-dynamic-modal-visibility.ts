import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { Modal } from '../event-types/modal';
import defaultModalProps from './types/default-modal-props';
import { ModalComponentMapper } from '../component-registration/modal-component-mapper';
import { ModalEventMap } from '../event-map/modal-event-map';
import UseManageModalVisibilityDefinition, {
  AllModalProps,
} from './definitions/use-modal-visibility-definition';
import { ModalComponentRegistrationTypes } from '../component-registration/modal-component-registration-types';

export const useDynamicModalVisibility =
  (): UseManageModalVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [componentKey, setComponentKey] =
      useState<ModalComponentRegistrationTypes | null>(null);

    const [componentProps, setComponentProps] = useState<AllModalProps>(
      defaultModalProps as AllModalProps
    );

    const emitter = eventSystem.fetchOrCreateEventEmitter<ModalEventMap>(
      Modal.MODAL
    );

    useEffect(() => {
      const handleVisibilityChange = (
        key: ModalComponentRegistrationTypes,
        props: AllModalProps
      ) => {
        console.log('useDynamicModalVisibility', key, props);

        setComponentKey(key);
        setComponentProps({
          ...defaultModalProps,
          ...props,
        });
      };

      emitter.on(Modal.MODAL, handleVisibilityChange);

      return () => {
        emitter.off(Modal.MODAL, handleVisibilityChange);
      };
    }, [emitter]);

    const closeModal = () => {
      const updatedProps = {
        ...componentProps,
        is_open: false,
      };

      setComponentProps(updatedProps);
      componentProps.on_close?.();
    };

    const ComponentToRender = componentKey
      ? ModalComponentMapper[componentKey]
      : null;

    return {
      componentKey,
      ComponentToRender,
      componentProps,
      closeModal,
    };
  };
