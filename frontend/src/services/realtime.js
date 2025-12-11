import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance = null;

function resolveApiOrigin() {
  const url = new URL(import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api');
  return url.origin;
}

export function initRealtime(token, userId, onMatch) {
  if (!token || !userId) {
    return;
  }

  const appKey = import.meta.env.VITE_PUSHER_APP_KEY;

  if (!appKey) {
    console.warn('Missing VITE_PUSHER_APP_KEY for real-time trading updates.');
    return;
  }

  if (echoInstance) {
    echoInstance.disconnect();
  }

  window.Pusher = Pusher;

  const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1';
  const scheme = import.meta.env.VITE_PUSHER_SCHEME ?? 'https';
  const host = import.meta.env.VITE_PUSHER_HOST ?? `ws-${cluster}.pusher.com`;
  const port = Number(import.meta.env.VITE_PUSHER_PORT ?? (scheme === 'https' ? 443 : 80));

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: appKey,
    cluster,
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authEndpoint: `${resolveApiOrigin()}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  });

  echoInstance.private(`user.${userId}`).listen('.OrderMatched', (payload) => {
    if (onMatch) {
      onMatch(payload);
    }
  });
}

export function disconnectRealtime() {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
}
