import Echo from 'laravel-echo';

import EchoInitializerDefinition from './definitions/echo-initializer-definition';

export default class EchoInitializer implements EchoInitializerDefinition {
  private echo?: Echo<'reverb'>;

  /**
   * Initialize Laravel Echo
   *
   * @throws Error - if csrf token is missing.
   */
  public initialize(): void {
    let token: HTMLMetaElement | null = document.head.querySelector(
      'meta[name="csrf-token"]'
    );

    if (token === null) {
      throw new Error('CSRF Token is missing. Failed to initialize.');
    }

    this.echo = new Echo({
      broadcaster: 'reverb',
      cluster: 'mt1',
      disableStats: true,
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: import.meta.env.VITE_REVERB_PORT,
      wssPort: import.meta.env.VITE_REVERB_PORT,
      enabledTransports: ['ws', 'wss'],
      namespace: 'App',
      auth: {
        headers: {
          'X-CSRF-TOKEN': token.content,
        },
      },
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      encrypted: false,
    });
  }

  /**
   * Fetch an instance of echo.
   *
   * @throws Error - if echo is not initialized.
   */
  public getEcho(): Echo<'reverb'> {
    if (this.echo) {
      return this.echo;
    }

    throw new Error('Echo has not been initialized.');
  }
}
