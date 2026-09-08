import ActiveBoonDefinition from '../../api/definitions/active-boon-definition';

export default interface ActiveBoonsContentProps {
  boons: ActiveBoonDefinition[];
  loading: boolean;
  error: string | null;
  success_message: string | null;
  mutation_error: string | null;
  filling_boon_id: number | null;
  removing_boon_id: number | null;
  on_view_source_item: (boon: ActiveBoonDefinition) => void;
  on_fill_up: (boonId: number) => void;
  on_remove: (boonId: number) => void;
  bounded_height?: boolean;
}
