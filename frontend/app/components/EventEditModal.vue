<script setup lang="ts">
type EventRow = {
  id: number
  title: string
  description: string | null
  starts_at: string | null
  ends_at: string | null
}

type EventPayload = {
  title: string
  description: string
  starts_at: string
  ends_at: string
}

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  event: EventRow | null
  pending?: boolean
  error?: string
}>()

const emit = defineEmits<{
  submit: [payload: EventPayload]
}>()

const form = reactive<EventPayload>({
  title: '',
  description: '',
  starts_at: '',
  ends_at: '',
})

function toLocalInput(value: string | null) {
  if (!value) {
    return ''
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ''
  }

  const pad = (unit: number) => unit.toString().padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

watch(
  () => props.event,
  (event) => {
    form.title = event?.title ?? ''
    form.description = event?.description ?? ''
    form.starts_at = toLocalInput(event?.starts_at ?? null)
    form.ends_at = toLocalInput(event?.ends_at ?? null)
  },
  { immediate: true }
)

function submitForm() {
  emit('submit', {
    title: form.title,
    description: form.description,
    starts_at: form.starts_at,
    ends_at: form.ends_at,
  })
}
</script>

<template>
  <UModal v-model:open="open" title="Edit event">
    <template #body>
      <form class="space-y-4" @submit.prevent="submitForm">
        <UFormField label="Title" name="event-title">
          <UInput v-model="form.title" placeholder="Event title" class="w-full" />
        </UFormField>

        <UFormField label="Description" name="event-description">
          <UTextarea v-model="form.description" placeholder="Description" class="w-full" />
        </UFormField>

        <UFormField label="Starts at" name="event-start">
          <UInput v-model="form.starts_at" type="datetime-local" class="w-full" />
        </UFormField>

        <UFormField label="Ends at" name="event-end">
          <UInput v-model="form.ends_at" type="datetime-local" class="w-full" />
        </UFormField>

        <UAlert
          v-if="props.error"
          color="error"
          variant="subtle"
          title="Update failed"
          :description="props.error"
        />

        <div class="flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" @click="open = false">
            Cancel
          </UButton>
          <UButton type="submit" :loading="props.pending">
            Save
          </UButton>
        </div>
      </form>
    </template>
  </UModal>
</template>
