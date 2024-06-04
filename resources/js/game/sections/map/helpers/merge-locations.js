var mergeLocations = function (locations, locationsToMerge) {
    var mergedMap = new Map();
    locations.forEach(function (location) {
        return mergedMap.set(location.id, location);
    });
    locationsToMerge.forEach(function (locationToMerge) {
        return mergedMap.set(locationToMerge.id, locationToMerge);
    });
    var mergedArray = Array.from(mergedMap.values());
    return mergedArray;
};
export { mergeLocations };
//# sourceMappingURL=merge-locations.js.map
