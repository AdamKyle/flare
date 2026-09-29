import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { useEffect, useState } from 'react';
import ReactMarkdown from 'react-markdown';

import GuideQuestDefinition from './api/definitions/guide-quest-definition';
import { useGuideQuestData } from './hooks/use-guide-quest-data';
import GuideQuestProps from './types/guide-quest-props';
import { useGuideWalkthrough } from './walkthrough/hooks/use-guide-walkthrough';

import { useGameData } from 'game-data/hooks/use-game-data';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GuideQuest = ({ on_close }: GuideQuestProps) => {
  const { setGuideQuestAttention } = useGameData();
  const { data, error, loading, handInGuideQuest, acknowledgeAttention } =
    useGuideQuestData();
  const walkthrough = useGuideWalkthrough();
  const [selectedQuestId, setSelectedQuestId] = useState<number | null>(null);
  const selectedQuest =
    data?.quests.find((quest) => quest.id === selectedQuestId) ??
    data?.quests[0] ??
    null;

  useEffect(() => {
    if (!data?.has_activity_attention) {
      return;
    }

    void acknowledgeAttention().then(() => setGuideQuestAttention(false));
  }, [
    acknowledgeAttention,
    data?.has_activity_attention,
    setGuideQuestAttention,
  ]);

  const renderBlocks = (blocks: GuideQuestDefinition['intro_text']) => {
    if (!blocks || blocks.length === 0) {
      return null;
    }

    return blocks.map((block) => (
      <div key={block.id} className="space-y-3">
        <ReactMarkdown>{block.content}</ReactMarkdown>
        {block.image_url ? (
          <img
            src={block.image_url}
            alt=""
            className="max-h-72 max-w-full rounded-sm object-contain"
          />
        ) : null}
      </div>
    ));
  };

  const handleStartWalkthrough = async (): Promise<void> => {
    if (!selectedQuest) {
      return;
    }

    const started = await walkthrough.startWalkthrough(selectedQuest.id);

    if (started) {
      on_close();
    }
  };

  const renderQuest = (quest: GuideQuestDefinition) => {
    const handIn = data?.can_hand_in.find(
      (entry) => entry.quest_id === quest.id
    );
    const completed = data?.completed_requirements.find(
      (entry) => entry.quest_id === quest.id
    );
    const levelComplete =
      completed?.completed_requirements.includes('required_level');

    return (
      <div className="space-y-5">
        <section aria-labelledby="guide-quest-story">
          <h4 id="guide-quest-story" className="mb-2 text-lg font-semibold">
            Story
          </h4>
          <div className="prose dark:prose-invert max-w-none">
            {renderBlocks(quest.intro_text)}
          </div>
        </section>

        {quest.instructions ? (
          <section aria-labelledby="guide-quest-information">
            <h4
              id="guide-quest-information"
              className="mb-2 text-lg font-semibold"
            >
              Information
            </h4>
            <ReactMarkdown>{quest.instructions}</ReactMarkdown>
          </section>
        ) : null}

        <section aria-labelledby="guide-quest-instructions">
          <h4
            id="guide-quest-instructions"
            className="mb-2 text-lg font-semibold"
          >
            Instructions
          </h4>
          <div className="md:hidden">
            {renderBlocks(quest.mobile_instructions)}
          </div>
          <div className="hidden md:block">
            {renderBlocks(quest.desktop_instructions)}
          </div>
        </section>

        <section aria-labelledby="guide-quest-requirements">
          <h4
            id="guide-quest-requirements"
            className="mb-2 text-lg font-semibold"
          >
            Required to Complete
          </h4>
          <ul className="list-disc pl-5">
            {quest.required_level ? (
              <li>
                Reach level {quest.required_level}:{' '}
                {levelComplete ? 'Complete' : 'In progress'}
              </li>
            ) : (
              <li>Complete the listed Guide Quest requirements.</li>
            )}
          </ul>
        </section>

        <section aria-labelledby="guide-quest-rewards">
          <h4 id="guide-quest-rewards" className="mb-2 text-lg font-semibold">
            Rewards
          </h4>
          <p>
            {quest.xp_reward ?? 0} XP, {quest.gold_reward ?? 0} Gold,{' '}
            {quest.gold_dust_reward ?? 0} Gold Dust, {quest.shards_reward ?? 0}{' '}
            Shards
          </p>
        </section>

        <div className="flex flex-col gap-3 sm:flex-row">
          {quest.has_walkthrough && quest.walkthrough_status !== 'completed' ? (
            <Button
              label={
                walkthrough.loading
                  ? 'Loading guide…'
                  : quest.walkthrough_status === 'in_progress'
                    ? 'Resume'
                    : 'Show me'
              }
              aria_busy={walkthrough.loading}
              disabled={walkthrough.loading}
              on_click={() => void handleStartWalkthrough()}
              variant={ButtonVariant.PRIMARY}
            />
          ) : null}
          {quest.walkthrough_status === 'completed' ? (
            <p role="status">Guided walkthrough complete</p>
          ) : null}
          <Button
            label="Hand In"
            disabled={!handIn?.can_hand_in}
            on_click={() => void handInGuideQuest(quest.id)}
            variant={ButtonVariant.SUCCESS}
          />
        </div>
      </div>
    );
  };

  const renderContent = () => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return <ApiErrorAlert apiError={error} />;
    }

    if (!data || data.quests.length === 0 || !selectedQuest) {
      return (
        <div className="py-8 text-center">
          <h4 className="text-lg font-semibold">You&apos;re all caught up</h4>
          <p className="mt-2 text-gray-600 dark:text-gray-300">
            You have no available Guide Quests right now. New guides will appear
            as you unlock features.
          </p>
        </div>
      );
    }

    return (
      <>
        {data.quests.length > 1 ? (
          <div className="mb-4">
            <label htmlFor="guide-quest-selection" className="mb-1 block">
              Available Guide Quest
            </label>
            <select
              id="guide-quest-selection"
              value={selectedQuest.id}
              onChange={(event) =>
                setSelectedQuestId(Number(event.target.value))
              }
              className="w-full rounded-sm border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-900"
            >
              {data.quests.map((quest) => (
                <option key={quest.id} value={quest.id}>
                  {quest.name}
                </option>
              ))}
            </select>
          </div>
        ) : null}
        <h3 className="mb-4 text-xl font-semibold">{selectedQuest.name}</h3>
        {walkthrough.error ? (
          <ApiErrorAlert apiError={walkthrough.error} />
        ) : null}
        {renderQuest(selectedQuest)}
      </>
    );
  };

  return (
    <ContainerWithTitle
      manageSectionVisibility={on_close}
      title={'The Next Guide Quest'}
    >
      <Card>{renderContent()}</Card>
    </ContainerWithTitle>
  );
};

export default GuideQuest;
