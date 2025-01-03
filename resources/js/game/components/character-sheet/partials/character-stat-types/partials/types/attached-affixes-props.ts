import BaseAttachedAffixesDetails from '../../../../../../api-definitions/items/base-attached-affixes-details';
import { StatTypes } from '../../../../enums/stat-types';

export default interface AttachedAffixesProps {
  attached_affixes: BaseAttachedAffixesDetails[] | [];
  stat_type: StatTypes;
}
