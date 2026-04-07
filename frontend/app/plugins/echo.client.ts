import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const publicConfig = config.public

  const key = publicConfig.pusherKey as string
  const cluster = publicConfig.pusherCluster as string | undefined
  const host = publicConfig.pusherHost as string | undefined
  const port = publicConfig.pusherPort as string | undefined
  const scheme = publicConfig.pusherScheme as string | undefined

  ;(window as any).Pusher = Pusher

  const echo = new Echo({
    broadcaster: 'pusher',
    key,
    cluster: cluster || 'mt1',
    wsHost: host || window.location.hostname,
    wsPort: Number(port || 6001),
    wssPort: Number(port || 6001),
    forceTLS: (scheme || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
  })

  ;(window as any).Echo = echo
})
