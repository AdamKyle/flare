export default interface MapActionsProps {

    move_player: (direction: string) => void,

    can_player_move: boolean,

    players_on_map: number,
}
