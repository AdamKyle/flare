import { ConjureType } from '../../api/enums/conjure-type';

export default interface ConjureConfirmationProps {
  type: ConjureType;
  celestial_name: string;
  gold_cost: number;
  gold_dust_cost: number;
  is_submitting: boolean;
  api_error: string | null;
  on_confirm: () => void;
  on_cancel: () => void;
}
