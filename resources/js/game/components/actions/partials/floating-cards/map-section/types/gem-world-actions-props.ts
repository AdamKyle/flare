import GemWorldStatusDefinition from '../../../../../map-section/api/definitions/gem-world-status-definition';

export default interface GemWorldActionsProps {
  status: GemWorldStatusDefinition | null;
  loading: boolean;
  context_error: string | null;
  can_move: boolean;
  exiting: boolean;
  exit_error: string | null;
  on_enter: () => void;
  on_view_effects: () => void;
  on_exit: () => void;
  on_open_gem_progress_history: () => void;
  on_open_all_active_gem_scrolls: () => void;
}
