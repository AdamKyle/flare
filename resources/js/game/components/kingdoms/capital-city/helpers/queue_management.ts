import BuildingsToUpgradeSection from "../buildings-to-upgrade-section";

export const removeFromQueue = (
    component: BuildingsToUpgradeSection,
    kingdomId: number,
    buildingId: number,
): void => {
    const upgradeQueue = JSON.parse(
        JSON.stringify(component.state.upgrade_queue),
    );

    const index = upgradeQueue.findIndex((queueData: any) => {
        return queueData.kingdomId === kingdomId;
    });

    if (index === -1) {
        return;
    }

    const buildingIndex = upgradeQueue[index].buildingIds.findIndex(
        (queueBuildingId: number) => {
            return queueBuildingId === buildingId;
        },
    );

    if (buildingIndex === -1) {
        return;
    }

    upgradeQueue[index].buildingIds.splice(buildingIndex, 1);

    if (upgradeQueue[index].buildingIds.length <= 0) {
        upgradeQueue.splice(index, 1);
    }

    component.setState({
        upgrade_queue: upgradeQueue,
    });
};

export const addToQueue = (
    component: BuildingsToUpgradeSection,
    kingdomId: number,
    buildingId: number,
): void => {
    const upgradeQueue = JSON.parse(
        JSON.stringify(component.state.upgrade_queue),
    );

    if (upgradeQueue.length <= 0) {
        upgradeQueue.push({
            kingdomId: kingdomId,
            buildingIds: [buildingId],
        });
    } else {
        const index = upgradeQueue.findIndex((queueData: any) => {
            return queueData.kingdomId === kingdomId;
        });

        if (index === -1) {
            upgradeQueue.push({
                kingdomId: kingdomId,
                buildingIds: [buildingId],
            });
        } else {
            upgradeQueue[index].buildingIds.push(buildingId);
        }
    }

    component.setState({
        upgrade_queue: upgradeQueue,
    });
};
