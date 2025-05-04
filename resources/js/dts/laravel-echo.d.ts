// resources/js/dts/laravel-echo.d.ts
import Pusher from 'pusher-js';

declare module 'laravel-echo' {
  interface Echo {
    connector: {
      pusher: {
        connection: {
          bind(event: string, callback: (err: unknown) => void): void;
          unbind(event: string): void;
        };
      };
    };
  }

  interface Channel {
    unsubscribe(): void;
    leave(): void;
  }

  interface PrivateChannel {
    error(status: unknown): this;
    whisper(event: string, data: any): this;
  }

  interface PresenceChannel {
    here(users: any[]): this;
    joining(user: any): this;
    leaving(user: any): this;
  }
}
