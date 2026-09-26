import { DelveEndReason } from '../enums/delve-end-reason';

const DELVE_END_REASON_LABELS: Record<string, string> = {
  [DelveEndReason.PLAYER_STOPPED]: 'Stopped by you',
  [DelveEndReason.SURVIVED]: 'Survived',
  [DelveEndReason.TIMEOUT]: 'Timed out',
  [DelveEndReason.ERROR]: 'Ended by an error',
  [DelveEndReason.DIED]: 'You died',
  [DelveEndReason.COMPLETED]: 'Completed',
};

export const resolveDelveEndReasonLabel = (reason: string): string =>
  DELVE_END_REASON_LABELS[reason] ?? 'Ended';
