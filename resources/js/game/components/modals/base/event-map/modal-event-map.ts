import { Modal } from '../event-types/modal';
import { ModalEventPayload } from '../payload/modal-event-payload';

export type ModalEventMap = {
  [Modal.MODAL]: ModalEventPayload;
};
