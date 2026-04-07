<script setup lang="ts">
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

const props = defineProps<{
  event: EventRow
  joinedCount?: number
  enrollPending?: boolean
  joined?: boolean
}>()

const emit = defineEmits<{
  enroll: [event: EventRow]
  leave: [event: EventRow]
}>()

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
</script>

<template>
  <UCard
    class="group overflow-hidden shadow-sm ring-1 ring-default transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/10 hover:ring-primary/30"
  >
    <div class="h-36 overflow-hidden bg-muted/10">
      <img
        v-if="props.event.img"
        :src="props.event.img"
        alt="Event image"
        class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-[1.03]"
      />
    </div>

    <div class="space-y-2 p-4">
      <NuxtLink
        :to="{ path: '/event-info', query: { id: props.event.id } }"
        class="line-clamp-1 text-lg font-semibold text-highlighted transition-colors duration-200 hover:text-primary group-hover:text-primary"
        :aria-label="`View event ${props.event.title}`"
      >
        {{ props.event.title }}
      </NuxtLink>
      <p class="line-clamp-1 text-sm text-toned">
        {{ props.event.description || 'No description provided.' }}
      </p>

      <div class="text-xs text-muted">
        <span class="font-semibold text-toned">Starts:</span>
        {{ formatDate(props.event.starts_at) }}
      </div>
      <div class="text-xs text-muted">
        <span class="font-semibold text-toned">Ends:</span>
        {{ formatDate(props.event.ends_at) }}
      </div>

      <div class="flex items-center justify-between gap-3 pt-2">
        <UButton
          v-if="!props.joined"
          size="sm"
          color="primary"
          variant="soft"
          :loading="props.enrollPending"
          @click="emit('enroll', props.event)"
        >
          Enroll
        </UButton>
        <UButton
          v-else
          size="sm"
          color="error"
          variant="soft"
          :loading="props.enrollPending"
          @click="emit('leave', props.event)"
        >
          Leave
        </UButton>
        <div class="rounded-md bg-muted/30 px-2 py-1 text-xs text-muted">
          Joined: <span class="font-semibold text-highlighted">{{ props.joinedCount ?? 0 }}</span>
        </div>
      </div>
    </div>
  </UCard>
</template>
