<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useSeoMeta({
  title: 'Event Dashboard',
  description: 'Participation dashboard'
})

type EventRow = {
  id: number
  title: string
  description?: string | null
  starts_at: string | null
  ends_at?: string | null
  joined_count?: number
  joined?: boolean
  limit?: number | null
}

type EventLogRow = {
  id: number
  action: 'join' | 'leave' | string
  created_at: string | null
  user?: {
    id: number
    name: string
    email: string
  } | null
}

type EventUserRow = {
  id: number
  name: string
  email: string
  role: 'admin' | 'user' | string
  created_at: string | null
  updated_at: string | null
}

const auth = useAuthStore()
const route = useRoute()

const event = ref<EventRow | null>(null)
const logs = ref<EventLogRow[]>([])
const eventUsers = ref<EventUserRow[]>([])
const logPage = ref(1)
const logLastPage = ref(1)
const logTotal = ref(0)
const userPage = ref(1)
const userLastPage = ref(1)
const userTotal = ref(0)
const pending = ref(false)
const logsPending = ref(false)
const usersPending = ref(false)
const pageError = ref('')
const usersError = ref('')
const loading = computed(() => pending.value && !event.value)

const weekLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

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

function dayIndex(value: string | null) {
  if (!value) {
    return null
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return null
  }

  const day = date.getDay()
  return day === 0 ? 6 : day - 1
}

const eventId = computed(() => {
  const raw = route.query.id
  const value = Array.isArray(raw) ? raw[0] : raw
  const parsed = Number(value)
  return Number.isNaN(parsed) ? null : parsed
})

type PaginationMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

function setLogMeta(meta: PaginationMeta) {
  logPage.value = meta.current_page
  logLastPage.value = meta.last_page
  logTotal.value = meta.total
}

function setUserMeta(meta: PaginationMeta) {
  userPage.value = meta.current_page
  userLastPage.value = meta.last_page
  userTotal.value = meta.total
}

async function fetchEvent(options: { page?: number, append?: boolean } = {}) {
  if (!eventId.value) {
    pageError.value = 'Event id is invalid.'
    event.value = null
    logs.value = []
    eventUsers.value = []
    return
  }

  const page = options.page ?? 1
  const append = options.append ?? false

  if (append) {
    logsPending.value = true
  } else {
    pending.value = true
  }
  pageError.value = ''

  try {
    const response = await auth.request<{ event: EventRow, logs?: EventLogRow[], meta?: PaginationMeta }>(
      `/events/${eventId.value}/dashboard?per_page=10&page=${page}`
    )
    event.value = response.event
    logs.value = append ? [...logs.value, ...(response.logs ?? [])] : (response.logs ?? [])
    if (response.meta) {
      setLogMeta(response.meta)
    }
  } catch (error) {
    pageError.value = readError(error, 'Unable to load dashboard data.')
  } finally {
    pending.value = false
    logsPending.value = false
  }
}

async function fetchEventUsers(page = 1) {
  if (!eventId.value) {
    usersError.value = 'Event id is invalid.'
    eventUsers.value = []
    return
  }

  usersPending.value = true
  usersError.value = ''

  try {
    const response = await auth.request<{ users: EventUserRow[], meta: PaginationMeta }>(
      `/events/${eventId.value}/users?per_page=10&page=${page}`
    )
    eventUsers.value = response.users
    setUserMeta(response.meta)
  } catch (error) {
    usersError.value = readError(error, 'Unable to load event users.')
  } finally {
    usersPending.value = false
  }
}

const participationByDay = computed((): { label: string, value: number }[] => {
  const totals = Array.from({ length: 7 }, () => 0)

  if (!event.value) {
  return totals.map((value, index) => ({
    label: weekLabels[index] ?? '',
      value,
    }))
  }

  const index = dayIndex(event.value.starts_at ?? null)
  if (index !== null) {
    totals[index] = event.value.joined_count ?? 0
  }

  return totals.map((value, idx) => ({
    label: weekLabels[idx] ?? '',
    value,
  }))
})

const maxParticipation = computed((): number => {
  return Math.max(1, ...participationByDay.value.map((item: { label: string, value: number }) => item.value))
})

type PieSlice = { key: string, label: string, count: number, start: number, end: number, percent: number, color: string }

const joinLeaveStats = computed((): { join: number, leave: number } => {
  const join = logs.value.filter((log: EventLogRow) => log.action === 'join').length
  const leave = logs.value.filter((log: EventLogRow) => log.action === 'leave').length
  return { join, leave }
})

const totalJoinLeave = computed((): number => {
  const total = joinLeaveStats.value.join + joinLeaveStats.value.leave
  return Math.max(1, total)
})

const pieSlices = computed((): PieSlice[] => {
  const slices = [
    { key: 'join', label: 'Join', count: joinLeaveStats.value.join, color: '#22c55e' },
    { key: 'leave', label: 'Leave', count: joinLeaveStats.value.leave, color: '#f97316' },
  ]

  let cursor = 0
  return slices.map((slice) => {
    const percent = slice.count / totalJoinLeave.value
    const start = cursor
    const end = cursor + percent
    cursor = end
    return {
      ...slice,
      start,
      end,
      percent,
    }
  })
})

function polarToCartesian(cx: number, cy: number, r: number, angle: number) {
  const rad = (angle - 90) * Math.PI / 180
  return {
    x: cx + r * Math.cos(rad),
    y: cy + r * Math.sin(rad),
  }
}

function describeArc(cx: number, cy: number, r: number, start: number, end: number) {
  const startAngle = start * 360
  const endAngle = end * 360
  const startPoint = polarToCartesian(cx, cy, r, endAngle)
  const endPoint = polarToCartesian(cx, cy, r, startAngle)
  const largeArc = endAngle - startAngle <= 180 ? '0' : '1'

  return [
    'M', cx, cy,
    'L', startPoint.x, startPoint.y,
    'A', r, r, 0, largeArc, 0, endPoint.x, endPoint.y,
    'Z',
  ].join(' ')
}

const capacityUsage = computed((): number | null => {
  if (!event.value || event.value.limit == null || event.value.limit <= 0) {
    return null
  }

  const joined = event.value.joined_count ?? 0
  return Math.min(100, Math.round((joined / event.value.limit) * 100))
})

function formatLogTime(value: string | null) {
  if (!value) {
    return '-'
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date)
}

function applyIncomingLog(log: EventLogRow) {
  logs.value = [log, ...logs.value].slice(0, 30)
}

function updateJoinedCount(count: number) {
  if (event.value) {
    event.value.joined_count = count
  }
}

const subscribedIds = new Set<number>()

function subscribeEvent(eventIdValue: number) {
  if (!import.meta.client || subscribedIds.has(eventIdValue)) {
    return
  }

  const echo = (window as unknown as { Echo?: any }).Echo
  if (!echo) {
    return
  }

  echo.channel(`event.${eventIdValue}`).listen('.event.attendees.updated', (payload: { joined_count?: number }) => {
    if (typeof payload?.joined_count === 'number') {
      updateJoinedCount(payload.joined_count)
    }
  })

  echo.channel(`event.${eventIdValue}`).listen('.event.log.created', (payload: EventLogRow) => {
    if (payload && payload.id) {
      applyIncomingLog(payload)
    }
  })

  subscribedIds.add(eventIdValue)
}

function unsubscribeEvent(eventIdValue: number) {
  if (!import.meta.client || !subscribedIds.has(eventIdValue)) {
    return
  }

  const echo = (window as unknown as { Echo?: any }).Echo
  if (echo) {
    echo.leave(`event.${eventIdValue}`)
  }

  subscribedIds.delete(eventIdValue)
}

watch(() => eventId.value, async (newId, oldId) => {
  if (typeof oldId === 'number') {
    unsubscribeEvent(oldId)
  }

  if (!newId) {
    event.value = null
    logs.value = []
    eventUsers.value = []
    return
  }

  await Promise.all([
    fetchEvent(),
    fetchEventUsers(1),
  ])
  subscribeEvent(newId)
}, { immediate: true })

onBeforeUnmount(() => {
  Array.from(subscribedIds).forEach((id) => unsubscribeEvent(id))
})

const hasMoreLogs = computed(() => logPage.value < logLastPage.value)

async function loadMoreLogs() {
  if (logsPending.value || !hasMoreLogs.value) {
    return
  }

  await fetchEvent({ page: logPage.value + 1, append: true })
}

async function goToUserPage(page: number) {
  if (page < 1 || page > userLastPage.value || page === userPage.value || usersPending.value) {
    return
  }

  await fetchEventUsers(page)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p v-if="event" class="text-sm text-toned">
          {{ event.title }}
        </p>
      </div>
    </div>

    <UAlert
      v-if="pageError"
      color="error"
      variant="subtle"
      title="Request failed"
      :description="pageError"
    />

    <div v-if="event" class="grid gap-6 lg:grid-cols-2">
      <UCard class="overflow-hidden">
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-highlighted">
                Weekly Participation Frequency
              </p>
              <p class="text-xs text-muted">
                Total joins per weekday
              </p>
            </div>
          </div>

          <div v-if="loading" class="grid h-44 grid-cols-7 items-end gap-2">
            <USkeleton v-for="index in 7" :key="index" class="h-full rounded-md" />
          </div>

          <ClientOnly v-else>
            <div class="grid h-44 grid-cols-7 items-end gap-2">
              <div
                v-for="item in participationByDay"
                :key="item.label"
                class="flex h-full flex-col items-center gap-2"
              >
                <div class="flex w-full flex-1 items-end">
                  <div
                    class="w-full rounded-md bg-primary/70 transition-all duration-300"
                    :style="{ height: `${Math.max(8, (item.value / maxParticipation) * 100)}%` }"
                  />
                </div>
                <div class="text-xs text-muted">
                  {{ item.label }}
                </div>
              </div>
            </div>
          </ClientOnly>
        </div>
      </UCard>

      <UCard class="overflow-hidden">
        <div class="space-y-4">
          <div>
            <p class="text-sm font-semibold text-highlighted">
              Join/Leave Ratio
            </p>
            <p class="text-xs text-muted">
              Distribution of joins vs leaves for this event
            </p>
          </div>

          <div v-if="loading" class="space-y-3">
            <USkeleton v-for="index in 5" :key="index" class="h-6 w-full rounded-md" />
          </div>

          <ClientOnly v-else>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="flex items-center justify-center">
                <svg width="180" height="180" viewBox="0 0 180 180" class="overflow-visible">
                  <circle cx="90" cy="90" r="72" fill="none" stroke="rgba(148,163,184,0.2)" stroke-width="24" />
                  <g>
                    <path
                      v-for="slice in pieSlices"
                      :key="slice.key"
                      :d="describeArc(90, 90, 72, slice.start, slice.end)"
                      :fill="slice.count ? slice.color : 'transparent'"
                    />
                  </g>
                </svg>
              </div>
              <div class="space-y-3">
                <div v-for="slice in pieSlices" :key="slice.key" class="flex items-center justify-between text-xs text-muted">
                  <div class="flex items-center gap-2">
                    <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: slice.color }" />
                    <span>{{ slice.label }}</span>
                  </div>
                  <span>{{ slice.count }}</span>
                </div>
              </div>
            </div>
          </ClientOnly>
        </div>
      </UCard>

      <UCard class="overflow-hidden lg:col-span-2">
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-highlighted">
                Capacity Overview
              </p>
              <p class="text-xs text-muted">
                Joined vs remaining seats
              </p>
            </div>
            <div class="text-xs text-muted">
              {{ event.joined_count ?? 0 }} joined
              <span v-if="event.limit != null"> / {{ event.limit }}</span>
              <span v-else> (Unlimited)</span>
            </div>
          </div>

          <div v-if="event.limit != null" class="space-y-3">
            <UProgress
              :model-value="capacityUsage ?? 0"
              :max="100"
              color="primary"
              size="md"
            />
            <div class="flex justify-between text-xs text-muted">
              <span>{{ event.joined_count ?? 0 }}</span>
              <span>{{ event.limit }}</span>
            </div>
          </div>

          <div v-else class="text-sm text-toned">
            This event has no participant limit.
          </div>
        </div>
      </UCard>

      <UCard class="overflow-hidden lg:col-span-2">
        <div class="space-y-4">
          <div>
            <p class="text-sm font-semibold text-highlighted">
              Join/Leave Timeline
            </p>
            <p class="text-xs text-muted">
              Live activity for this event
            </p>
          </div>

          <div v-if="loading" class="space-y-3">
            <USkeleton v-for="index in 4" :key="index" class="h-8 w-full rounded-md" />
          </div>

          <ClientOnly v-else>
            <div v-if="logs.length" class="space-y-3">
              <div
                v-for="log in logs"
                :key="log.id"
                class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-default/70 bg-muted/10 px-3 py-2 text-sm"
              >
                <div class="flex items-center gap-2">
                  <span
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                    :class="log.action === 'join' ? 'bg-primary/20 text-primary' : 'bg-orange-100 text-orange-600'"
                  >
                    {{ log.action === 'join' ? 'Join' : 'Leave' }}
                  </span>
                  <span class="text-toned">
                    {{ log.user?.name || 'Unknown user' }}
                  </span>
                  <span class="text-xs text-muted">
                    {{ log.user?.email }}
                  </span>
                </div>
                <div class="text-xs text-muted">
                  {{ formatLogTime(log.created_at) }}
                </div>
              </div>
            </div>

            <div v-else class="text-sm text-muted">
              No activity yet.
            </div>
          </ClientOnly>

          <div v-if="hasMoreLogs" class="flex justify-center">
            <UButton
              color="neutral"
              variant="soft"
              size="sm"
              :loading="logsPending"
              @click="loadMoreLogs"
            >
              View more
            </UButton>
          </div>
        </div>
      </UCard>

      <UCard class="overflow-hidden lg:col-span-2">
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-highlighted">
                Event Users
              </p>
              <p class="text-xs text-muted">
                All users registered in this event
              </p>
            </div>
            <div class="text-xs text-muted">
              Total {{ userTotal }}
            </div>
          </div>

          <UAlert
            v-if="usersError"
            color="error"
            variant="subtle"
            title="Request failed"
            :description="usersError"
          />

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-default">
              <thead class="bg-muted/40">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted">ID</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted">Name</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted">Email</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted">Role</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted">Created</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-default">
                <tr v-if="usersPending" v-for="index in 4" :key="`event-user-skeleton-${index}`">
                  <td class="px-4 py-4"><USkeleton class="h-5 w-10" /></td>
                  <td class="px-4 py-4"><USkeleton class="h-5 w-32" /></td>
                  <td class="px-4 py-4"><USkeleton class="h-5 w-48" /></td>
                  <td class="px-4 py-4"><USkeleton class="h-6 w-16 rounded-full" /></td>
                  <td class="px-4 py-4"><USkeleton class="h-5 w-32" /></td>
                </tr>

                <tr v-else v-for="user in eventUsers" :key="user.id">
                  <td class="px-4 py-4 text-sm text-toned">{{ user.id }}</td>
                  <td class="px-4 py-4 text-sm font-medium text-highlighted">{{ user.name }}</td>
                  <td class="px-4 py-4 text-sm text-toned">{{ user.email }}</td>
                  <td class="px-4 py-4 text-sm">
                    <UBadge :color="user.role === 'admin' ? 'primary' : 'neutral'" variant="soft">
                      {{ user.role }}
                    </UBadge>
                  </td>
                  <td class="px-4 py-4 text-sm text-toned">{{ formatLogTime(user.created_at) }}</td>
                </tr>

                <tr v-if="!eventUsers.length && !usersPending">
                  <td colspan="5" class="px-4 py-10 text-center text-sm text-muted">
                    No users found for this event.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between border-t border-default pt-3 text-sm text-muted">
            <div>
              Page {{ userPage }} / {{ userLastPage }}
            </div>
            <div class="flex gap-2">
              <UButton
                color="neutral"
                variant="soft"
                size="sm"
                :disabled="userPage <= 1 || usersPending"
                @click="goToUserPage(userPage - 1)"
              >
                Previous
              </UButton>
              <UButton
                color="neutral"
                variant="soft"
                size="sm"
                :disabled="userPage >= userLastPage || usersPending"
                @click="goToUserPage(userPage + 1)"
              >
                Next
              </UButton>
            </div>
          </div>
        </div>
      </UCard>
    </div>

    <UCard v-else-if="!pending" class="flex items-center justify-center py-10 text-sm text-muted">
      Event not found.
    </UCard>
  </div>
</template>
