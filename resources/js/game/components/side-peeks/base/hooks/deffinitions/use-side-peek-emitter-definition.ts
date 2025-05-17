import { SidePeekComponentPropsMap } from '../../component-registration/side-peek-component-props-map';
import { SidePeek } from '../../event-types/side-peek';

export default interface UseSidePeekEmitterDefinition {
  emit<K extends keyof SidePeekComponentPropsMap>(
    event: typeof SidePeek.SIDE_PEEK,
    key: K,
    props: SidePeekComponentPropsMap[K]
  ): void;
}
