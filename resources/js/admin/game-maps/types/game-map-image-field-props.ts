export default interface GameMapImageFieldProps {
  current_image_url: string | null;
  error?: string;
  acknowledgement_error?: string;
  replacement_image_acknowledged: boolean;
  show_replacement_warning: boolean;
  on_change: (file: File | null) => void;
  on_acknowledgement_change: (acknowledged: boolean) => void;
}
