import { SidePeekComponentMapper } from '../../component-registration/side-peek-component-mapper';
import { SidePeekComponentPropsMap } from '../../component-registration/side-peek-component-props-map';
import { SidePeekComponentRegistrationEnum } from '../../component-registration/side-peek-component-registration-enum';

export type AllSidePeekProps =
  SidePeekComponentPropsMap[keyof SidePeekComponentPropsMap];

export default interface UseManageSidePeekVisibilityDefinition {
  componentKey: SidePeekComponentRegistrationEnum | null;
  ComponentToRender:
    | (typeof SidePeekComponentMapper)[keyof typeof SidePeekComponentMapper]
    | null;
  componentProps: AllSidePeekProps;
  closeSidePeek: () => void;
}
