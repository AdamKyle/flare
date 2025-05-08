import ModalProps from 'ui/modal/types/modal-props';

export default interface LocationDetailsProps extends ModalProps {
  character_id: number;
  location_id: number;
}
