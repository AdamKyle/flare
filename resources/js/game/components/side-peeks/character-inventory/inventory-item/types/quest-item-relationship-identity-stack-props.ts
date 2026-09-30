export type QuestItemRelationshipIdentityKind = 'location' | 'map' | 'monster';

export default interface QuestItemRelationshipIdentityStackProps {
  kind: QuestItemRelationshipIdentityKind;
  id: number;
  on_close: () => void;
}
