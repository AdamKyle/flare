export enum LocationPin {
  CHRISTMAS_TREE = 'christmas-tree-x-pin',
  SNOWMAN = 'snowman-x-pin',
}

export const LOCATION_PIN_LABELS: Record<LocationPin, string> = {
  [LocationPin.CHRISTMAS_TREE]: 'Christmas Tree',
  [LocationPin.SNOWMAN]: 'Snowman',
};

export const LOCATION_PIN_VALUES: LocationPin[] = [
  LocationPin.CHRISTMAS_TREE,
  LocationPin.SNOWMAN,
];

export const isLocationPin = (value: string | number): value is LocationPin => {
  if (typeof value !== 'string') {
    return false;
  }

  return LOCATION_PIN_VALUES.some((locationPin) => locationPin === value);
};
