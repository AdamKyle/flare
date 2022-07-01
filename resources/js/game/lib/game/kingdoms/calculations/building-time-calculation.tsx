import BuildingDetails from "../building-details";

export default class BuildingTimeCalculation {

    /**
     * Calculates the time for one level.
     *
     * @param building
     * @param toLevel
     * @param timeReduction
     */
    calculateViewTime(building: BuildingDetails, toLevel: number, timeReduction: number) {
        return this.calculateTimeNeeded(building, toLevel, timeReduction,1);
    }

    /**
     * Calculates the time needed for multiple levels.
     *
     * @param building
     * @param toLevel
     * @param timeReduction
     * @param levels
     */
    calculateTimeNeeded(building: BuildingDetails, toLevel: number, timeReduction: number, levels?: number) {
        let buildingCurrentLevel   = building.level;
        const levelsToPurchase     = typeof levels !== 'undefined' ? levels : toLevel;
        let time                   = 0;

        for (let i = levelsToPurchase; i > 0; i--) {
            const newLevel = buildingCurrentLevel + 1;

            let toBuild = newLevel + building.raw_time_to_build;

            toBuild = (toBuild + toBuild * building.raw_time_increase)

            time += toBuild;

            buildingCurrentLevel++;
        }

        return time - time * timeReduction;
    }
}
