import Echo from 'laravel-echo';

export default interface EchoInitializerDefinition {
  /**
   * Initializes laravel echo with default configuration values.
   */
  initialize: () => void;

  /**
   * Gets the instance of echo
   */
  getEcho: () => Echo<'reverb'>;
}
