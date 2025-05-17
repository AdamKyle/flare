import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseModalEmitterDefinition from './definitions/use-modal-emitter-definition';
import { ModalEventMap } from '../event-map/modal-event-map';
import { Modal } from '../event-types/modal';

export const useModalEmitter = (): UseModalEmitterDefinition => {
  const eventSystem = useEventSystem();

  return eventSystem.fetchOrCreateEventEmitter<ModalEventMap>(Modal.MODAL);
};
