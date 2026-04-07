<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useSeoMeta({
  title: 'Home',
  description: 'Sample homepage'
})

type EventRow = {
  id: number
  host_id: number
  title: string
  description: string | null
  img?: string | null
  starts_at: string | null
  ends_at: string | null
  created_at: string | null
  updated_at: string | null
  joined_count?: number
  joined?: boolean
}

const auth = useAuthStore()
const toast = useToast()

const events = ref<EventRow[]>([])
const pending = ref(false)
const pageError = ref('')
const joinedCounts = ref<Record<number, number>>({})
const enrollPending = ref<Record<number, boolean>>({})

const subscribedIds = new Set<number>()

function readError(error: unknown, fallback: string) {
  if (!error || typeof error !== 'object') {
    return fallback
  }

  const fetchError = error as {
    data?: { message?: string, errors?: Record<string, string[]> }
    message?: string
    statusMessage?: string
  }

  const errors = fetchError.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]
    if (Array.isArray(first) && first[0]) {
      return first[0]
    }
  }

  return fetchError.data?.message || fetchError.statusMessage || fetchError.message || fallback
}

function parseDate(value: string | null) {
  if (!value) {
    return null
  }

  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? null : date
}

const sortedEvents = computed(() => {
  return [...events.value].sort((a, b) => {
    const aDate = parseDate(a.starts_at) ?? parseDate(a.created_at)
    const bDate = parseDate(b.starts_at) ?? parseDate(b.created_at)

    if (!aDate && !bDate) {
      return 0
    }

    if (!aDate) {
      return 1
    }

    if (!bDate) {
      return -1
    }

    return aDate.getTime() - bDate.getTime()
  })
})

function setJoinedCount(eventId: number, count: number) {
  joinedCounts.value = {
    ...joinedCounts.value,
    [eventId]: count,
  }
}

function setEnrollPending(eventId: number, value: boolean) {
  enrollPending.value = {
    ...enrollPending.value,
    [eventId]: value,
  }
}

async function fetchEvents() {
  pending.value = true
  pageError.value = ''

  try {
    const response = await auth.request<{ events: EventRow[] }>('/events')
    events.value = response.events
    joinedCounts.value = response.events.reduce<Record<number, number>>((acc, event) => {
      acc[event.id] = event.joined_count ?? 0
      return acc
    }, {})
  } catch (error) {
    pageError.value = readError(error, 'Unable to load events.')
  } finally {
    pending.value = false
  }
}

function setJoinedState(eventId: number, joined: boolean) {
  const target = events.value.find((event) => event.id === eventId)
  if (target) {
    target.joined = joined
  }
}

async function enrollEvent(event: EventRow) {
  setEnrollPending(event.id, true)

  try {
    await auth.request(`/events/${event.id}/join`, { method: 'POST' })
    const current = joinedCounts.value[event.id]
    if (typeof current === 'number') {
      setJoinedCount(event.id, current + 1)
    } else {
      setJoinedCount(event.id, 1)
    }
    setJoinedState(event.id, true)
    toast.add({
      title: 'Enrollment successful',
      description: `You have joined "${event.title}".`,
      color: 'primary',
    })
  } catch (error) {
    toast.add({
      title: 'Enrollment failed',
      description: readError(error, 'Unable to enroll in event.'),
      color: 'error',
    })
  } finally {
    setEnrollPending(event.id, false)
  }
}

async function leaveEvent(event: EventRow) {
  setEnrollPending(event.id, true)

  try {
    await auth.request(`/events/${event.id}/leave`, { method: 'POST' })
    const current = joinedCounts.value[event.id] ?? 0
    setJoinedCount(event.id, Math.max(0, current - 1))
    setJoinedState(event.id, false)
    toast.add({
      title: 'Left event',
      description: `You left "${event.title}".`,
      color: 'primary',
    })
  } catch (error) {
    toast.add({
      title: 'Leave failed',
      description: readError(error, 'Unable to leave event.'),
      color: 'error',
    })
  } finally {
    setEnrollPending(event.id, false)
  }
}

function subscribeEvent(eventId: number) {
  if (!import.meta.client || subscribedIds.has(eventId)) {
    return
  }

  const echo = (window as unknown as { Echo?: any }).Echo
  if (!echo) {
    return
  }

  echo.channel(`event.${eventId}`).listen('.event.attendees.updated', (payload: { joined_count?: number }) => {
    if (typeof payload?.joined_count === 'number') {
      setJoinedCount(eventId, payload.joined_count)
    }
  })

  subscribedIds.add(eventId)
}

function unsubscribeEvent(eventId: number) {
  if (!import.meta.client || !subscribedIds.has(eventId)) {
    return
  }

  const echo = (window as unknown as { Echo?: any }).Echo
  if (echo) {
    echo.leave(`event.${eventId}`)
  }

  subscribedIds.delete(eventId)
}

onMounted(async () => {
  await fetchEvents()
})

watch(events, (newEvents: EventRow[]) => {
  const newIds = new Set(newEvents.map((event) => event.id))
  newIds.forEach((id: number) => subscribeEvent(id))
  Array.from(subscribedIds).forEach((id) => {
    if (!newIds.has(id)) {
      unsubscribeEvent(id)
    }
  })
}, { immediate: true })

onBeforeUnmount(() => {
  Array.from(subscribedIds).forEach((id) => unsubscribeEvent(id))
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <UButton to=  "/events" color="primary" variant="soft" icon="i-lucide-calendar-days">
        My events
      </UButton>
    </div>

    <UAlert
      v-if="pageError"
      color="error"
      variant="subtle"
      title="Request failed"
      :description="pageError"
    />

    <div v-if="pending" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <UCard v-for="index in 6" :key="index" class="overflow-hidden">
        <USkeleton class="h-36 w-full" />
        <div class="space-y-3 p-4">
          <USkeleton class="h-5 w-1/2" />
          <USkeleton class="h-4 w-3/4" />
          <USkeleton class="h-4 w-2/3" />
        </div>
      </UCard>
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <EventCard
        v-for="event in sortedEvents"
        :key="event.id"
        :event="event"
        :joined-count="joinedCounts[event.id] ?? 0"
        :enroll-pending="enrollPending[event.id] ?? false"
        :joined="event.joined ?? false"
        @enroll="enrollEvent"
        @leave="leaveEvent"
      />

      <UCard
        v-if="!sortedEvents.length"
        class="col-span-full flex items-center justify-center py-10 text-sm text-muted"
      >
        No events found.
      </UCard>
    </div>
  </div>
 </template>
