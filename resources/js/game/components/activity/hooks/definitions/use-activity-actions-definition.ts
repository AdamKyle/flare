export default interface UseActivityActionsDefinition {
  has_new_announcements: boolean;
  open_announcements: () => void;
  open_guide_quests: () => void;
  open_donations: () => void;
}
