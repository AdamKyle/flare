var BuildingTimeCalculation = (function () {
    function BuildingTimeCalculation() {}
    BuildingTimeCalculation.prototype.convertToHours = function (time) {
        return time / 60;
    };
    BuildingTimeCalculation.prototype.isHours = function (time) {
        return time / 60 > 1;
    };
    BuildingTimeCalculation.prototype.calculateViewTime = function (
        building,
        toLevel,
        timeReduction,
    ) {
        return this.calculateTimeNeeded(building, toLevel, timeReduction, 1);
    };
    BuildingTimeCalculation.prototype.calculateRebuildTime = function (
        building,
        timeReduction,
    ) {
        return building.rebuild_time - building.rebuild_time * timeReduction;
    };
    BuildingTimeCalculation.prototype.calculateTimeNeeded = function (
        building,
        toLevel,
        timeReduction,
        levels,
    ) {
        var buildingCurrentLevel = building.level;
        var levelsToPurchase = typeof levels !== "undefined" ? levels : toLevel;
        var totalLevels = buildingCurrentLevel + levelsToPurchase;
        var rawTimeIncrease = building.raw_time_to_build;
        var time;
        time = totalLevels + rawTimeIncrease;
        time = time + time * building.raw_time_increase;
        return Math.floor(time - time * timeReduction);
    };
    return BuildingTimeCalculation;
})();
export default BuildingTimeCalculation;
//# sourceMappingURL=building-time-calculation.js.map
