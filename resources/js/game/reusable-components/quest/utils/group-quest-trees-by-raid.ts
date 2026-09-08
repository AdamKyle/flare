import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import QuestRaidGroup from '../types/quest-raid-group';

const UNRESOLVED_RAID_LABEL = 'Unresolved Raid';

export const groupQuestTreesByRaid = (
  questTrees: QuestTreeNodeDefinition[]
): QuestRaidGroup[] => {
  const groups: QuestRaidGroup[] = [];
  const groupIndexByKey = new Map<string, number>();

  questTrees.forEach((quest) => {
    const raidId = quest.raid?.id ?? null;
    const raidName = quest.raid?.name ?? UNRESOLVED_RAID_LABEL;
    const key = raidId === null ? UNRESOLVED_RAID_LABEL : `raid-${raidId}`;

    const existingIndex = groupIndexByKey.get(key);

    if (existingIndex === undefined) {
      groupIndexByKey.set(key, groups.length);
      groups.push({ raid_id: raidId, raid_name: raidName, quests: [quest] });

      return;
    }

    groups[existingIndex].quests.push(quest);
  });

  return groups.sort(
    (a, b) =>
      a.raid_name.localeCompare(b.raid_name) ||
      (a.raid_id ?? 0) - (b.raid_id ?? 0)
  );
};
