export default interface UseActivityActionsDefinition {
  has_activity_attention: boolean;
  has_new_announcements: boolean;
  has_guide_quest_attention: boolean;
  open_announcements: () => void;
  open_guide_quests: () => void;
  open_donations: () => void;
}
