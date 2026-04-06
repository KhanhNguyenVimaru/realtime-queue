<script setup lang="ts">
type EventPayload = {
  title: string
  description: string
  starts_at: string
  ends_at: string
}

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
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

watch(open, (value) => {
  if (!value) {
    form.title = ''
    form.description = ''
    form.starts_at = ''
    form.ends_at = ''
  }
})

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
  <UModal v-model:open="open" title="Create event">
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
          title="Create failed"
          :description="props.error"
        />

        <div class="flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" @click="open = false">
            Cancel
          </UButton>
          <UButton type="submit" :loading="props.pending">
            Create
          </UButton>
        </div>
      </form>
    </template>
  </UModal>
</template>
