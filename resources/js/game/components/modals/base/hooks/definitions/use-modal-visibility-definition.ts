import { ModalComponentMapper } from '../../component-registration/modal-component-mapper';
import { ModalComponentPropsMap } from '../../component-registration/modal-component-props';
import { ModalComponentRegistrationTypes } from '../../component-registration/modal-component-registration-types';

export type AllModalProps =
  ModalComponentPropsMap[keyof ModalComponentPropsMap];

export default interface UseManageModalVisibilityDefinition {
  componentKey: ModalComponentRegistrationTypes | null;
  ComponentToRender:
    | (typeof ModalComponentMapper)[keyof typeof ModalComponentMapper]
    | null;
  componentProps: AllModalProps;
  closeModal: () => void;
}
