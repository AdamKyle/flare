import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface RaceImportSidePeekProps extends SidePeekProps {
  on_imported: () => void;
}
