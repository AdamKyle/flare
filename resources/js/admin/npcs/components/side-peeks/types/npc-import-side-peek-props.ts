import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface NpcImportSidePeekProps extends SidePeekProps {
  on_imported: () => void;
}
