import { GuideQuestContentBlockDefinition } from '../../api/definitions/guide-quest-definition';

export default interface GuideQuestContentSectionProps {
  title: string;
  blocks: GuideQuestContentBlockDefinition[] | null;
}
