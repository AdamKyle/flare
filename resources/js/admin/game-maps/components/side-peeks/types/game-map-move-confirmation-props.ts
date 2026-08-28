export default interface GameMapMoveConfirmationProps {
  entity_type_label: string;
  entity_label: string;
  origin_x: number;
  origin_y: number;
  destination_x: number;
  destination_y: number;
  is_moving: boolean;
  api_error: string | null;
  on_confirm: () => void;
  on_cancel: () => void;
  on_clear_error: () => void;
}
