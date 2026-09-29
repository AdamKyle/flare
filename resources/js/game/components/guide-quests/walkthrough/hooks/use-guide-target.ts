import { GuideTarget } from '../registry/guide-targets';

export const useGuideTarget = (target: GuideTarget) => ({
  'data-guide-target': target,
});
