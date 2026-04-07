<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useSeoMeta({
  title: 'Events',
  description: 'Events page'
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

type EventPayload = {
  title: string
  description: string
  img?: string | null
  starts_at: string
  ends_at: string
}

const auth = useAuthStore()
const toast = useToast()

const events = ref<EventRow[]>([])
const pending = ref(false)
const pageError = ref('')
const formError = ref('')
const deleteError = ref('')

const isCreateOpen = ref(false)
const isEditOpen = ref(false)
const isDetailOpen = ref(false)
const editingEvent = ref<EventRow | null>(null)
const detailEvent = ref<EventRow | null>(null)

const search = ref('')
const sortBy = ref<'latest' | 'oldest'>('latest')

const headers = [
  'ID',
  'Title',
  'Starts',
  'Ends',
  'Actions',
]

const skeletonRows = Array.from({ length: 4 }, (_, index) => `skeleton-${index}`)

function formatDate(value: string | null) {
  if (!value) {
    return '-'
  }

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

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

function buildQuery() {
  const params = new URLSearchParams()

  const term = search.value.trim()
  if (term) {
    params.set('search', term)
  }

  if (sortBy.value) {
    params.set('sort_by', sortBy.value)
  }

  const hostId = auth.currentUser?.id
  if (hostId) {
    params.set('host_id', String(hostId))
  }

  const query = params.toString()
  return query ? `/events?${query}` : '/events'
}

function toggleSort() {
  sortBy.value = sortBy.value === 'latest' ? 'oldest' : 'latest'
}

async function fetchEvents() {
  pending.value = true
  pageError.value = ''

  try {
    const response = await auth.request<{ events: EventRow[] }>(buildQuery())
    events.value = response.events
  } catch (error) {
    pageError.value = readError(error, 'Unable to load events.')
  } finally {
    pending.value = false
  }
}

async function createEvent(payload: EventPayload) {
  pending.value = true
  formError.value = ''

  try {
    await auth.request('/events', {
      method: 'POST',
      body: payload,
    })

    isCreateOpen.value = false
    await fetchEvents()
  } catch (error) {
    formError.value = readError(error, 'Unable to create event.')
  } finally {
    pending.value = false
  }
}

function openEditModal(event: EventRow) {
  formError.value = ''
  editingEvent.value = event
  isEditOpen.value = true
}

function openDetailModal(event: EventRow) {
  detailEvent.value = event
  isDetailOpen.value = true
}

async function updateEvent(payload: EventPayload) {
  if (!editingEvent.value) {
    return
  }

  pending.value = true
  formError.value = ''

  try {
    await auth.request(`/events/${editingEvent.value.id}`, {
      method: 'PUT',
      body: payload,
    })

    isEditOpen.value = false
    editingEvent.value = null
    await fetchEvents()
  } catch (error) {
    formError.value = readError(error, 'Unable to update event.')
  } finally {
    pending.value = false
  }
}

function confirmDelete(event: EventRow) {
  return new Promise<boolean>((resolve) => {
    let resolved = false
    let timeoutId: ReturnType<typeof setTimeout> | null = null

    const finalize = (value: boolean) => {
      if (resolved) {
        return
      }
      resolved = true
      if (timeoutId) {
        clearTimeout(timeoutId)
      }
      resolve(value)
    }

    const toastItem = toast.add({
      title: 'Delete event?',
      description: `Delete event "${event.title}"?`,
      color: 'warning',
      duration: 5000,
      actions: [
        {
          label: 'Cancel',
          color: 'neutral',
          variant: 'ghost',
          onClick: () => {
            finalize(false)
            toast.remove(toastItem.id)
          },
        },
        {
          label: 'OK',
          color: 'error',
          onClick: () => {
            finalize(true)
            toast.remove(toastItem.id)
          },
        },
      ],
    })

    timeoutId = setTimeout(() => {
      toast.remove(toastItem.id)
      finalize(false)
    }, 5200)
  })
}

async function deleteEvent(event: EventRow) {
  deleteError.value = ''

  const confirmed = await confirmDelete(event)
  if (!confirmed) {
    return
  }

  pending.value = true

  try {
    await auth.request(`/events/${event.id}`, {
      method: 'DELETE',
    })

    await fetchEvents()
  } catch (error) {
    deleteError.value = readError(error, 'Unable to delete event.')
  } finally {
    pending.value = false
  }
}

onMounted(async () => {
  await fetchEvents()
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  searchTimeout = setTimeout(() => {
    fetchEvents()
  }, 350)
})

watch(sortBy, () => {
  fetchEvents()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-end">
      <div class="flex w-full flex-col gap-3 md:w-1/2 md:flex-row md:justify-end">
        <UFormField name="search-events" class="w-full md:w-1/2">
          <UInput v-model="search" placeholder="Search by title or description" class="w-full" />
        </UFormField>
      </div>

      <UButton
        color="primary"
        variant="solid"
        class="md:ml-3 w-fit"
        icon="i-lucide-arrow-up-down"
        @click="toggleSort"
      >
        {{ sortBy === 'latest' ? 'Newest' : 'Oldest' }}
      </UButton>

      <UButton
        color="primary"
        variant="solid"
        class="md:ml-3 w-fit"
        icon="i-lucide-plus"
        @click="isCreateOpen = true"
      >
        New event
      </UButton>
    </div>

    <UAlert
      v-if="pageError"
      color="error"
      variant="subtle"
      title="Request failed"
      :description="pageError"
    />

    <UAlert
      v-if="deleteError"
      color="error"
      variant="subtle"
      title="Delete failed"
      :description="deleteError"
    />

    <UCard class="overflow-hidden shadow-sm ring-1 ring-default">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-default">
          <thead class="bg-muted/40">
            <tr>
              <th
                v-for="header in headers"
                :key="header"
                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted"
              >
                {{ header }}
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-default">
            <tr v-if="pending" v-for="row in skeletonRows" :key="row" class="align-top">
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-10" />
              </td>
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-48" />
              </td>
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-32" />
              </td>
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-32" />
              </td>
            </tr>

            <tr v-else v-for="event in events" :key="event.id" class="align-top">
              <td class="px-4 py-4 text-sm text-toned">
                {{ event.id }}
              </td>
              <td class="px-4 py-4 text-sm font-medium text-highlighted">
                {{ event.title }}
              </td>
              <td class="px-4 py-4 text-sm text-toned">
                {{ formatDate(event.starts_at) }}
              </td>
              <td class="px-4 py-4 text-sm text-toned">
                {{ formatDate(event.ends_at) }}
              </td>
              <td class="px-4 py-4">
                <div class="flex flex-wrap gap-2">
                  <UButton
                    color="neutral"
                    variant="soft"
                    size="sm"
                    icon="i-lucide-eye"
                    @click="openDetailModal(event)"
                  >
                    Detail
                  </UButton>

                  <UButton
                    color="primary"
                    variant="soft"
                    size="sm"
                    icon="i-lucide-pencil"
                    @click="openEditModal(event)"
                  >
                    Edit
                  </UButton>

                  <UButton
                    color="error"
                    variant="soft"
                    size="sm"
                    icon="i-lucide-trash-2"
                    @click="deleteEvent(event)"
                  >
                    Delete
                  </UButton>
                </div>
              </td>
            </tr>

            <tr v-if="!events.length && !pending">
              <td colspan="5" class="px-4 py-10 text-center text-sm text-muted">
                No events found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </UCard>

    <EventCreateModal
      v-model:open="isCreateOpen"
      :pending="pending"
      :error="formError"
      @submit="createEvent"
    />

    <EventEditModal
      v-model:open="isEditOpen"
      :event="editingEvent"
      :pending="pending"
      :error="formError"
      @submit="updateEvent"
    />

    <EventDetailModal
      v-model:open="isDetailOpen"
      :event="detailEvent"
    />
  </div>
</template>
