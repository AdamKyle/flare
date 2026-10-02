import GuideQuestResponseDefinition from '../../definitions/guide-quest-response-definition';

export default interface UseGuideQuestsDefinition {
  data: GuideQuestResponseDefinition | null;
  error: string | null;
  loading: boolean;
  fetchGuideQuests: () => Promise<void>;
  handInGuideQuest: (guideQuestId: number) => Promise<void>;
  acknowledgeAttention: () => Promise<boolean>;
}
