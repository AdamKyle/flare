import BuildingDetails from "../../../../sections/kingdoms/buildings/deffinitions/building-details";

export default class BuildingTimeCalculation {

    /**
     * Convert hours to minutes.
     *
     * @param time
     */
    convertToHours(time: number): number {
        return time / 60;
    }

    /**
     * Is time in hours?
     *
     * @param time
     */
    isHours(time: number): boolean {
        return (time / 60) > 1;
    }

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
     * Calculate the rebuild time needed.
     *
     * @param building
     * @param timeReduction
     */
    calculateRebuildTime(building: BuildingDetails, timeReduction: number) {
        return (building.rebuild_time - building.rebuild_time * timeReduction);
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
        const totalLevels          = buildingCurrentLevel + levelsToPurchase
        const rawTimeIncrease      = building.raw_time_to_build;
        let time;

        time = totalLevels + rawTimeIncrease;
        time = time + time * building.raw_time_increase;

        return Math.floor(time - time * timeReduction);
    }
}
