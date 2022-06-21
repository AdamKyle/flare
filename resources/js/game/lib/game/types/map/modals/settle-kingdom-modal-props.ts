import {ClassArray, ClassDictionary} from "clsx";
import LocationDetails from "../../../map/types/location-details";

export default interface SettleKingdomModalProps  {

    is_open: boolean;

    handle_close: () => void;

    character_id: number;

    map_id: number;

}
