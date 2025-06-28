import { LocationInfoTypes } from '../../enums/location-info-types';

export default interface EnemyStrengthIncreaseProps {
  enemy_strength_increase: number | null;
  handle_on_info_click: (infoType: LocationInfoTypes) => void;
}
