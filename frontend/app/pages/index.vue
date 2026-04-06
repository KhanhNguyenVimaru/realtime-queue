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
}

const auth = useAuthStore()

const events = ref<EventRow[]>([])
const pending = ref(false)
const pageError = ref('')

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

async function fetchEvents() {
  pending.value = true
  pageError.value = ''

  try {
    const response = await auth.request<{ events: EventRow[] }>('/events')
    events.value = response.events
  } catch (error) {
    pageError.value = readError(error, 'Unable to load events.')
  } finally {
    pending.value = false
  }
}

onMounted(async () => {
  await fetchEvents()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <UButton to="/events" color="primary" variant="soft" icon="i-lucide-calendar-days">
        Manage events
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
