import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface SkillImportSidePeekProps extends SidePeekProps {
  on_imported: () => void;
}
