export default interface UseOpenItemDetailsDefinition {
  openServerMessageItem: (character_id: number, slot_id: number) => void;
  openServerMessageQuestItem: (character_id: number, slot_id: number) => void;
}
