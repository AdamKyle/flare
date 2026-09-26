import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import CharacterSkillDetailSidePeekProps from './types/character-skill-detail-side-peek-props';
import SkillItemContributionDefinition from '../../character-sheet/skills/api/definitions/skill-item-contribution-definition';
import SkillTrainingResponseDefinition from '../../character-sheet/skills/api/definitions/skill-training-response-definition';
import { useSkillDetail } from '../../character-sheet/skills/api/hooks/use-skill-detail';
import { useSkillTraining } from '../../character-sheet/skills/api/hooks/use-skill-training';
import SkillDetailFacts from '../../character-sheet/skills/components/skill-detail-facts';
import SkillItemsAffectingList from '../../character-sheet/skills/components/skill-items-affecting-list';
import SkillTrainingForm from '../../character-sheet/skills/components/skill-training-form';
import { SkillBonusItemSource } from '../../character-sheet/skills/enums/skill-bonus-item-source';
import InventoryItemDetailStack from '../character-inventory/inventory-item/inventory-item-detail-stack';
import QuestItemSlotDetailStack from '../character-inventory/inventory-item/quest-item-slot-detail-stack';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const CharacterSkillDetailSidePeek = ({
  character_id: characterId,
  skill_id: skillId,
  on_training_skills_changed: onTrainingSkillsChanged,
}: CharacterSkillDetailSidePeekProps): ReactNode => {
  const { skill, loading, error, refetch } = useSkillDetail(
    characterId,
    skillId
  );
  const {
    submitting,
    error: trainingError,
    train,
    cancel,
  } = useSkillTraining(characterId);

  const [trainingMessage, setTrainingMessage] = useState<string | null>(null);
  const [selectedItem, setSelectedItem] =
    useState<SkillItemContributionDefinition | null>(null);

  const applyTrainingResponse = (
    response: SkillTrainingResponseDefinition | null
  ) => {
    if (response === null) {
      return;
    }

    setTrainingMessage(response.message);
    onTrainingSkillsChanged(response.skills.training_skills);
    refetch();
  };

  const handleTrain = async (xpPercentage: number) => {
    setTrainingMessage(null);

    applyTrainingResponse(await train(skillId, xpPercentage));
  };

  const handleCancel = async () => {
    setTrainingMessage(null);

    applyTrainingResponse(await cancel(skillId));
  };

  const handleCloseItem = () => {
    setSelectedItem(null);
  };

  const renderTraining = (): ReactNode => {
    if (skill === null || !skill.can_train || skill.is_locked) {
      return null;
    }

    return (
      <>
        <Separator />
        <SkillTrainingForm
          skill={skill}
          submitting={submitting}
          error={trainingError}
          success_message={trainingMessage}
          on_train={(xpPercentage) => void handleTrain(xpPercentage)}
          on_cancel={() => void handleCancel()}
        />
      </>
    );
  };

  const renderSelectedItem = (): ReactNode => {
    if (selectedItem === null) {
      return null;
    }

    if (selectedItem.source === SkillBonusItemSource.QUEST) {
      return (
        <QuestItemSlotDetailStack
          character_id={characterId}
          slot_id={selectedItem.slot_id}
          aria_label={selectedItem.name}
          on_close={handleCloseItem}
        />
      );
    }

    return (
      <InventoryItemDetailStack
        slot_id={selectedItem.slot_id}
        character_id={characterId}
        aria_label={selectedItem.name}
        on_close={handleCloseItem}
        on_action={handleCloseItem}
        show_actions={false}
      />
    );
  };

  if (loading && skill === null) {
    return (
      <div className="px-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (error !== null || skill === null) {
    return (
      <div className="px-4">
        <ApiErrorAlert apiError={error ?? 'Unable to load this Skill.'} />
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4 px-4">
      <SkillDetailFacts skill={skill} />
      {renderTraining()}
      <Separator />
      <SkillItemsAffectingList
        skill_id={skill.id}
        items={skill.items_affecting_skill}
        on_open_item={setSelectedItem}
      />
      {renderSelectedItem()}
    </div>
  );
};

export default CharacterSkillDetailSidePeek;
