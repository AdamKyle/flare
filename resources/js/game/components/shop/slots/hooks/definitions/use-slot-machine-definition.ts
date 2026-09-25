import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SpinSlotsRewardDefinition from '../../api/definitions/spin-slots-reward-definition';
import SlotCooldown from '../../types/slot-cooldown';
import SlotSymbolPresentation from '../../types/slot-symbol-presentation';

export default interface UseSlotMachineDefinition {
  symbols: SlotSymbolPresentation[];
  spin_cost: number | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  spin_error: AxiosErrorDefinition | null;
  is_spinning: boolean;
  can_spin: boolean;
  cooldown: SlotCooldown | null;
  target_rolls: number[] | null;
  result_message: string | null;
  result_reward: SpinSlotsRewardDefinition | null;
  handle_spin: () => void;
  handle_reel_stopped: () => void;
}
