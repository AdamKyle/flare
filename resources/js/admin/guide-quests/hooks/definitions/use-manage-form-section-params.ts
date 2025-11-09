import GuideQuestDefinition from "../../api/definitions/guide-quest-definition";

export default interface UseManagementFormSectionParams {
  on_update: (formData: Partial<GuideQuestDefinition>) => void;

}