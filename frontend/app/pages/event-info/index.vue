<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useSeoMeta({
  title: 'Event info',
  description: 'Event information'
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
const route = useRoute()

const event = ref<EventRow | null>(null)
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

function formatDate(value: string | null) {
  const date = parseDate(value)
  if (!date) {
    return '-'
  }

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date)
}

const eventId = computed(() => {
  const raw = route.query.id
  const value = Array.isArray(raw) ? raw[0] : raw
  const parsed = Number(value)
  return Number.isNaN(parsed) ? null : parsed
})

async function fetchEvent() {
  if (!eventId.value) {
    pageError.value = 'Event id is invalid.'
    return
  }

  pending.value = true
  pageError.value = ''

  try {
    const response = await auth.request<{ event?: EventRow } | EventRow>(`/events/${eventId.value}`)
    if ('event' in response && response.event) {
      event.value = response.event
    } else {
      event.value = response as EventRow
    }
  } catch (error) {
    pageError.value = readError(error, 'Unable to load event.')
  } finally {
    pending.value = false
  }
}

onMounted(async () => {
  await fetchEvent()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <UButton to="/events" color="primary" variant="soft" icon="i-lucide-arrow-left">
        Back to events
      </UButton>
    </div>

    <UAlert
      v-if="pageError"
      color="error"
      variant="subtle"
      title="Request failed"
      :description="pageError"
    />

    <UCard v-if="pending" class="overflow-hidden">
      <div class="flex flex-col md:flex-row">
        <USkeleton class="h-56 w-full md:h-auto md:w-1/3" />
        <div class="flex-1 space-y-4 p-6">
          <USkeleton class="h-7 w-2/3" />
          <USkeleton class="h-4 w-full" />
          <USkeleton class="h-4 w-4/5" />
          <USkeleton class="h-4 w-3/5" />
        </div>
      </div>
    </UCard>

    <UCard v-else-if="event" class="overflow-hidden">
      <div class="flex flex-col md:flex-row">
        <div class="relative h-56 w-full overflow-hidden bg-muted/10 md:h-auto md:w-1/3">
          <img
            v-if="event.img"
            :src="event.img"
            alt="Event image"
            class="h-full w-full object-cover"
          />
          <div v-else class="flex h-full w-full items-center justify-center text-sm text-muted">
            No image provided
          </div>
        </div>

        <div class="flex-1 space-y-4 p-6">
          <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-primary">
              Event info
            </p>
            <h1 class="text-2xl font-semibold text-highlighted md:text-3xl">
              {{ event.title }}
            </h1>
          </div>

          <p class="text-sm text-toned whitespace-pre-line">
            {{ event.description || 'No description provided.' }}
          </p>

          <div class="border-t border-default pt-4 text-sm text-muted">
            <div class="flex flex-col gap-2">
              <div>
                <span class="font-semibold text-toned">Starts:</span>
                {{ formatDate(event.starts_at) }}
              </div>
              <div>
                <span class="font-semibold text-toned">Ends:</span>
                {{ formatDate(event.ends_at) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </UCard>

    <UCard v-else class="flex items-center justify-center py-10 text-sm text-muted">
      Event not found.
    </UCard>
  </div>
</template>
