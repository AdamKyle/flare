export enum AutomationType {
  EXPLORING = 0,
  DELVE = 1,
  FACTION_LOYALTY = 2,
}

const AUTOMATION_TYPE_VALUES: AutomationType[] = [
  AutomationType.EXPLORING,
  AutomationType.DELVE,
  AutomationType.FACTION_LOYALTY,
];

/**
 * Narrow a factual `number` value down to a known Automation type, without a
 * forced type assertion at each call site.
 */
export const isAutomationType = (value: number): value is AutomationType =>
  AUTOMATION_TYPE_VALUES.some((automationType) => automationType === value);
