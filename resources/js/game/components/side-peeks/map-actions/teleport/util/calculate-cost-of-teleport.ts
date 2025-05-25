import CalculateCostOfTeleportDefinition from './definitions/calculate-cost-of-teleport-definition';
import CalculateCostOfTeleportParams from './types/calculate-cost-of-teleport-params';
import CalculateDistanceParams from './types/calculate-distance-params';

export const calculateCostOfTeleport = ({
  character_position,
  new_character_position,
  character_gold,
}: CalculateCostOfTeleportParams): CalculateCostOfTeleportDefinition => {
  const distance = calculateDistance({
    character_position,
    new_character_position,
  });

  const time = Math.round(distance / 60);
  const cost = time * 1_000;
  let canAfford = true;

  if (cost > character_gold) {
    canAfford = false;
  }

  return {
    time,
    cost,
    can_afford: canAfford,
  };
};

const calculateDistance = ({
  character_position,
  new_character_position,
}: CalculateDistanceParams): number => {
  const distanceX = Math.pow(
    new_character_position.x_position - character_position.x_position,
    2
  );
  const distanceY = Math.pow(
    new_character_position.y_position - character_position.y_position,
    2
  );

  let distance = distanceX + distanceY;
  distance = Math.sqrt(distance);

  if (isNaN(distance)) {
    return 0;
  }

  return Math.round(distance);
};
