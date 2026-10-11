import { GuideWalkthroughEvent } from '../enums/guide-walkthrough-event';

export default interface GuideWalkthroughEventMap {
  [eventType: string]: undefined;
  [GuideWalkthroughEvent.FIGHT_STARTED]: undefined;
  [GuideWalkthroughEvent.COMBAT_ACTION_SUCCEEDED]: undefined;
  [GuideWalkthroughEvent.ITEM_EQUIPPED]: undefined;
  [GuideWalkthroughEvent.EXPLORATION_FIGHT_COMPLETED]: undefined;
  [GuideWalkthroughEvent.SKILL_TRAINING_STARTED]: undefined;
  [GuideWalkthroughEvent.FACTION_ASSISTANCE_STARTED]: undefined;
}
