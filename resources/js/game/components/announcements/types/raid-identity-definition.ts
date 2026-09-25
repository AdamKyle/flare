import { RaidType } from '../enums/RaidType';

export default interface RaidIdentityDefinition {
  id: number;
  raid_type: RaidType;
  name: string;
}
