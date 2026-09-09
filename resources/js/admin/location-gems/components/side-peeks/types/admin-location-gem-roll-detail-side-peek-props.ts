import AdminRolledGemDefinition from '../../../../shared/gems/api/definitions/admin-rolled-gem-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface AdminLocationGemRollDetailSidePeekProps extends SidePeekProps {
  location_gem_id: number;
  roll: AdminRolledGemDefinition;
  on_activated: () => void;
}
