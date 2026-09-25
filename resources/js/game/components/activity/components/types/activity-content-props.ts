export default interface ActivityContentProps {
  has_new_announcements: boolean;
  on_open_announcements: () => void;
  on_open_guide_quests: () => void;
  on_open_donations: () => void;
  on_action_selected: () => void;
}
