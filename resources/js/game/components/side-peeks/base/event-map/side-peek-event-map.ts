import {SidePeek} from "../event-types/side-peek";
import {SidePeekEventPayload} from "../payload/side-peek-event-payload";

export type SidePeekEventMap = {
  [SidePeek.SIDE_PEEK]: SidePeekEventPayload;
};
