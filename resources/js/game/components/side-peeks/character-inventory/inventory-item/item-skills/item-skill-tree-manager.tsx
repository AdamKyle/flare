import { AnimatePresence } from 'framer-motion';
import React, { ReactNode, useMemo, useState } from 'react';

import InventoryStackBody from '../../components/inventory-stack-body';
import { useStopTrainingItemSkill } from './api/hooks/use-stop-training-item-skill';
import { useTrainItemSkill } from './api/hooks/use-train-item-skill';
import ItemSkillDetails, {
  buildItemSkillFooterOptions,
} from './components/item-skill-details';
import ItemSkillTreeNode from './components/item-skill-tree-node';
import ItemSkillDetailsProps from './types/item-skill-details-props';
import ItemSkillTreeManagerProps from './types/item-skill-tree-manager-props';
import ItemSkillTreeNodeData from './types/item-skill-tree-node-data';
import { buildItemSkillTreeData } from './utils/build-item-skill-tree-data';
import { flattenItemSkills } from './utils/resolve-item-skill-state';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import TreeNodeDefinition from 'ui/tree/definitions/tree-node-definition';
import TreeMobileMode from 'ui/tree/enums/tree-mobile-mode';
import Tree from 'ui/tree/tree';

const ItemSkillTreeManager = ({
  character_id: characterId,
  item,
  refetch,
}: ItemSkillTreeManagerProps): ReactNode => {
  const [detailStack, setDetailStack] = useState<number[]>([]);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const train = useTrainItemSkill(characterId, item.item_id);
  const stop = useStopTrainingItemSkill(characterId, item.item_id);
  const treeData = useMemo(
    () =>
      buildItemSkillTreeData(item.item_skills, item.item_skill_progressions),
    [item.item_skills, item.item_skill_progressions]
  );
  const allSkills = useMemo(
    () => flattenItemSkills(item.item_skills),
    [item.item_skills]
  );

  const canManageItemSkills = item.can_manage_item_skills;

  const resetTransientMessages = () => {
    setSuccessMessage(null);
    train.reset_error();
    stop.reset_error();
  };

  const findNodeByProgression = (
    progressionId: number
  ): TreeNodeDefinition<ItemSkillTreeNodeData> | undefined =>
    treeData.nodes.find((node) => node.data.progression.id === progressionId);

  const findNodeBySkill = (
    skillId: number | null
  ): TreeNodeDefinition<ItemSkillTreeNodeData> | undefined =>
    treeData.nodes.find((node) => node.data.skill.id === skillId);

  const handleOpenRootDetail = (progressionId: number) => {
    resetTransientMessages();
    setDetailStack([progressionId]);
  };

  const handleOpenParentDetail = (progressionId: number) => {
    resetTransientMessages();
    setDetailStack((previousStack) => [...previousStack, progressionId]);
  };

  const handleCloseTopDetail = () => {
    resetTransientMessages();
    setDetailStack((previousStack) => previousStack.slice(0, -1));
  };

  const handleMutation = async (
    progressionId: number,
    mutation: (progressionId: number) => Promise<string | null>
  ): Promise<void> => {
    const message = await mutation(progressionId);

    if (message === null) {
      return;
    }

    setSuccessMessage(message);
    await refetch();
  };

  const renderNode = (
    node: TreeNodeDefinition<ItemSkillTreeNodeData>
  ): ReactNode => <ItemSkillTreeNode {...node.data} />;

  const buildDetailProps = (
    node: TreeNodeDefinition<ItemSkillTreeNodeData>,
    isTopLayer: boolean
  ): ItemSkillDetailsProps => {
    const parentNode = findNodeBySkill(node.data.skill.parent_id);
    const progressionId = node.data.progression.id;

    return {
      ...node.data,
      parent:
        allSkills.find((skill) => skill.id === node.data.skill.parent_id) ??
        null,
      on_open_parent: parentNode
        ? () => handleOpenParentDetail(parentNode.data.progression.id)
        : null,
      can_manage_item_skills: canManageItemSkills,
      processing: train.loading || stop.loading,
      error_message: isTopLayer
        ? (train.error?.message ?? stop.error?.message ?? null)
        : null,
      success_message: isTopLayer ? successMessage : null,
      on_train: () => void handleMutation(progressionId, train.mutate),
      on_stop: () => void handleMutation(progressionId, stop.mutate),
    };
  };

  const renderDetailLayer = (layerIndex: number): ReactNode => {
    const progressionId = detailStack[layerIndex];

    if (progressionId === undefined) {
      return null;
    }

    const node = findNodeByProgression(progressionId);

    if (!node) {
      return null;
    }

    const props = buildDetailProps(node, layerIndex === detailStack.length - 1);

    return (
      <StackedCard
        key={progressionId}
        on_close={handleCloseTopDetail}
        aria_label={`Item Skill Details: ${node.data.skill.name}`}
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <InventoryStackBody footer_options={buildItemSkillFooterOptions(props)}>
          <ItemSkillDetails {...props} />
        </InventoryStackBody>
        <AnimatePresence>{renderDetailLayer(layerIndex + 1)}</AnimatePresence>
      </StackedCard>
    );
  };

  const renderManagementNotice = (): ReactNode => {
    if (canManageItemSkills) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        Equip this Artifact to manage its skill training. You can still inspect
        the full Ancestral Skill Tree.
      </Alert>
    );
  };

  const renderIncompleteNotice = (): ReactNode => {
    if (!treeData.is_incomplete) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        This skill tree data is incomplete. Some skills cannot be shown.
      </Alert>
    );
  };

  return (
    <>
      <div className="space-y-4 px-4">
        <div>
          <h2 className="text-xl font-bold text-gray-800 dark:text-gray-200">
            Ancestral Skill Tree
          </h2>
          <p className="mt-2 text-gray-700 dark:text-gray-300">
            All skills in this tree stack together. Select a skill to view its
            progression and modifiers.
          </p>
          <a
            href="/information/ancestral-items"
            target="_blank"
            rel="noopener noreferrer"
            className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 mt-2 inline-block font-semibold underline focus:ring-2 focus:outline-none"
          >
            Ancestral Items help docs
            <span className="sr-only"> (opens in a new tab)</span>
          </a>
        </div>
        {renderManagementNotice()}
        {renderIncompleteNotice()}
        <Tree<ItemSkillTreeNodeData>
          nodes={treeData.nodes}
          branches={treeData.branches}
          render_node={renderNode}
          on_node_activate={(node) =>
            handleOpenRootDetail(node.data.progression.id)
          }
          accessibility_label="Ancestral Skill Tree"
          mobile_mode={TreeMobileMode.TREE}
          default_zoom={0.7}
        />
      </div>
      <AnimatePresence mode="wait">{renderDetailLayer(0)}</AnimatePresence>
    </>
  );
};

export default ItemSkillTreeManager;
