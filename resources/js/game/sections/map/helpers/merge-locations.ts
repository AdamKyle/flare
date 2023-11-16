import LocationDetails from "../types/location-details";

const mergeLocations = (locations: LocationDetails[], locationsToMerge: LocationDetails[]): LocationDetails[] => {
    const mergedMap = new Map<number, LocationDetails>();

    locations.forEach((location) => mergedMap.set(location.id, location));

    locationsToMerge.forEach((locationToMerge) => mergedMap.set(locationToMerge.id, locationToMerge));

    const mergedArray = Array.from(mergedMap.values());

    return mergedArray;
}

export { mergeLocations }
