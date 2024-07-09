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

export const addAllBuildingsToQueue = (
    component: BuildingsToUpgradeSection,
): void => {
    const upgradeQueue: any[] = JSON.parse(
        JSON.stringify(component.state.upgrade_queue),
    );

    component.state.table_data.slice(0, 200).forEach((data: any) => {
        if (upgradeQueue.length <= 0) {
            upgradeQueue.push({
                kingdomId: data.kingdom_id,
                buildingIds: [data.building_id],
            });
        } else {
            const index = upgradeQueue.findIndex((queueData: any) => {
                return queueData.kingdomId === data.kingdom_id;
            });

            if (index === -1) {
                upgradeQueue.push({
                    kingdomId: data.kingdom_id,
                    buildingIds: [data.building_id],
                });
            } else {
                if (
                    !upgradeQueue[index].buildingIds.includes(data.building_id)
                ) {
                    upgradeQueue[index].buildingIds.push(data.building_id);
                }
            }
        }
    });

    component.setState({
        upgrade_queue: upgradeQueue,
    });
};

export const removeAllFromQueue = (
    component: BuildingsToUpgradeSection,
): void => {
    component.setState({
        upgrade_queue: [],
    });
};
