import { AutomationType } from './automation-type';

/**
 * The automation currently occupying the Character, as resolved by the
 * backend `AutomationRestrictionService`/`CharacterSheetBaseInfoTransformer`.
 * The frontend treats this as identity only, never as a restriction policy.
 */
export default interface ActiveAutomationDefinition {
  type: AutomationType;
  name: string;
  timer_seconds: number;
}
