export default interface UseSetActionApiParams {
  character_id: number;
  on_success: (successMessage: string) => void;
}
