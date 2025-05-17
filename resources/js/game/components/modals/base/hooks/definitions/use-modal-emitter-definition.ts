import { ModalComponentPropsMap } from '../../component-registration/modal-component-props';
import { Modal } from '../../event-types/modal';

export default interface UseModalEmitterDefinition {
  emit<K extends keyof ModalComponentPropsMap>(
    event: typeof Modal.MODAL,
    key: K,
    props: ModalComponentPropsMap[K]
  ): void;
}
