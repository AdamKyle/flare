import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { useState } from 'react';
import ReactMarkdown from 'react-markdown';

import GuideQuestDefinition, {
  GuideQuestContentBlockDefinition,
} from './api/definitions/guide-quest-definition';
import GuideQuestRequirements from './components/guide-quest-requirements';
import GuideQuestSelector from './components/guide-quest-selector';
import { useGuideQuestData } from './hooks/use-guide-quest-data';
import GuideQuestProps from './types/guide-quest-props';
import { useGuideWalkthrough } from './walkthrough/hooks/use-guide-walkthrough';
import CurrencyDisplay from '../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../reusable-components/currency/enums/currency-type';
import QuestCurrencyRow from '../../reusable-components/quest/types/quest-currency-row';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import DetailGridRow from 'ui/detail-grid/detail-grid-row';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const GuideQuest = ({ on_close }: GuideQuestProps) => {
  const { data, error, loading, handInGuideQuest } = useGuideQuestData();
  const walkthrough = useGuideWalkthrough();
  const [selectedQuestId, setSelectedQuestId] = useState<number | null>(null);
  const selectedQuest =
    data?.quests.find((quest) => quest.id === selectedQuestId) ??
    data?.quests[0] ??
    null;

  const renderBlockImage = (imageUrl: string | null) => {
    if (!imageUrl) {
      return null;
    }

    return (
      <img
        src={imageUrl}
        alt=""
        className="max-h-72 max-w-full rounded-sm object-contain"
      />
    );
  };

  const renderBlock = (block: GuideQuestContentBlockDefinition) => (
    <div key={block.id} className="space-y-3">
      <ReactMarkdown>{block.content}</ReactMarkdown>
      {renderBlockImage(block.image_url)}
    </div>
  );

  const renderBlocks = (blocks: GuideQuestDefinition['intro_text']) => {
    if (!blocks || blocks.length === 0) {
      return null;
    }

    return blocks.map(renderBlock);
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

  const renderInformation = (instructions: string | null) => {
    if (!instructions) {
      return null;
    }

    return (
      <section aria-labelledby="guide-quest-information">
        <h4
          id="guide-quest-information"
          className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold"
        >
          Information
        </h4>
        <ReactMarkdown>{instructions}</ReactMarkdown>
      </section>
    );
  };

  const renderShowMeButton = (quest: GuideQuestDefinition) => {
    if (!quest.has_walkthrough) {
      return null;
    }

    return (
      <Button
        label={walkthrough.loading ? 'Loading guide…' : 'Show me'}
        aria_busy={walkthrough.loading}
        disabled={walkthrough.loading}
        on_click={() => void handleStartWalkthrough()}
        variant={ButtonVariant.PRIMARY}
      />
    );
  };

  const renderActions = (quest: GuideQuestDefinition, canHandIn: boolean) => (
    <div className="flex flex-col gap-3 sm:flex-row">
      {renderShowMeButton(quest)}
      <Button
        label="Hand In"
        disabled={!canHandIn}
        on_click={() => void handInGuideQuest(quest.id)}
        variant={ButtonVariant.SUCCESS}
      />
    </div>
  );

  const renderRewards = (quest: GuideQuestDefinition) => {
    const currencyRows: QuestCurrencyRow[] = [
      {
        label: 'Gold',
        currency: CurrencyType.GOLD,
        value: quest.gold_reward ?? 0,
      },
      {
        label: 'Gold Dust',
        currency: CurrencyType.GOLD_DUST,
        value: quest.gold_dust_reward ?? 0,
      },
      {
        label: 'Shards',
        currency: CurrencyType.SHARDS,
        value: quest.shards_reward ?? 0,
      },
    ].filter((row) => row.value !== 0);
    const xpReward = quest.xp_reward ?? 0;

    const renderCurrencyRow = (row: QuestCurrencyRow) => (
      <React.Fragment key={row.label}>
        <Dt>{row.label}</Dt>
        <Dd>
          <CurrencyDisplay
            currency={row.currency}
            amount={row.value}
            display_mode={CurrencyDisplayMode.EXACT}
            show_label={false}
          />
        </Dd>
      </React.Fragment>
    );

    return (
      <section aria-labelledby="guide-quest-rewards">
        <h4
          id="guide-quest-rewards"
          className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold"
        >
          Rewards
        </h4>
        <Dl>
          {currencyRows.map(renderCurrencyRow)}
          {xpReward !== 0 && (
            <>
              <Dt>XP</Dt>
              <Dd>{formatNumberWithCommas(xpReward)}</Dd>
            </>
          )}
        </Dl>
      </section>
    );
  };

  const renderQuest = (quest: GuideQuestDefinition) => {
    const handIn = data?.can_hand_in.find(
      (entry) => entry.quest_id === quest.id
    );
    const completed = data?.completed_requirements.find(
      (entry) => entry.quest_id === quest.id
    );
    const completedRequirements = completed?.completed_requirements ?? [];
    const batchCraftedItemRequirements =
      completed?.required_batch_crafted_item_requirements ?? [];

    return (
      <div className="flex flex-col gap-6">
        {renderActions(quest, handIn?.can_hand_in ?? false)}

        <Separator additional_css="my-1" />

        <section aria-labelledby="guide-quest-story">
          <h4
            id="guide-quest-story"
            className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold"
          >
            Story
          </h4>
          <div className="prose dark:prose-invert max-w-none">
            {renderBlocks(quest.intro_text)}
          </div>
        </section>

        <Separator additional_css="my-1" />

        {renderInformation(quest.instructions)}

        {quest.instructions && <Separator additional_css="my-1" />}

        <section aria-labelledby="guide-quest-instructions">
          <h4
            id="guide-quest-instructions"
            className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold"
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

        <Separator additional_css="my-1" />

        <DetailGridRow>
          <GuideQuestRequirements
            quest={quest}
            completed_requirements={completedRequirements}
            batch_crafted_item_requirements={batchCraftedItemRequirements}
          />
          {renderRewards(quest)}
        </DetailGridRow>
      </div>
    );
  };

  const renderWalkthroughError = () => {
    if (!walkthrough.error) {
      return null;
    }

    return <ApiErrorAlert apiError={walkthrough.error} />;
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
        <GuideQuestSelector
          quests={data.quests}
          selected_quest_id={selectedQuest.id}
          on_select={setSelectedQuestId}
        />
        <h3 className="text-glacier-900 dark:text-glacier-100 mb-5 text-xl font-semibold">
          {selectedQuest.name}
        </h3>
        {renderWalkthroughError()}
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
